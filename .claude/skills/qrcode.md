# Skill: qrcode
> Módulo M-06 · QR Code e Compartilhamento
> Leia também: CLAUDE.md · docs/design-system.md seções 5.1, 5.4

---

## Contexto do módulo

Gera QR Code do cartão e QR Code PIX (padrão EMV/BR Code).
Downloads em PNG e SVG.
Modal de compartilhamento no painel com cópia de link.

---

## Pacotes

```bash
# Já incluído no T-004
composer require simplesoftwareio/simple-qrcode
```

---

## QrCodeService

```php
// app/Services/QrCodeService.php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * QR Code da URL do cartão público
     */
    public function forCard(Card $card, string $format = 'svg'): string
    {
        $url = url('/u/' . $card->slug);

        return match($format) {
            'png' => QrCode::format('png')
                ->size(400)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($url),
            default => QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($url),
        };
    }

    /**
     * QR Code PIX — padrão EMV/BR Code (Payload Manual)
     * Suporta apenas chave PIX simples (CPF, CNPJ, telefone, e-mail, aleatória)
     */
    public function forPix(string $pixKey, string $merchantName, float $amount = 0.00): string
    {
        $payload = $this->buildPixPayload($pixKey, $merchantName, $amount);

        return QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->errorCorrection('M')
            ->generate($payload);
    }

    private function buildPixPayload(string $pixKey, string $merchantName, float $amount): string
    {
        $name      = substr(mb_convert_encoding($merchantName, 'ASCII'), 0, 25);
        $city      = 'PORTO VELHO';
        $txid      = 'CARD' . strtoupper(Str::random(8));
        $amountStr = $amount > 0 ? sprintf('%08.2f', $amount) : '';

        // Merchant Account Information
        $gui  = $this->tlv('00', 'br.gov.bcb.pix') . $this->tlv('01', $pixKey);
        $mai  = $this->tlv('26', $gui);

        // Composição do payload
        $payload =
            $this->tlv('00', '01') .           // Payload Format Indicator
            $mai .                              // Merchant Account Information
            $this->tlv('52', '0000') .          // Merchant Category Code
            $this->tlv('53', '986') .           // Transaction Currency (BRL)
            ($amountStr ? $this->tlv('54', $amountStr) : '') .
            $this->tlv('58', 'BR') .            // Country Code
            $this->tlv('59', $name) .           // Merchant Name
            $this->tlv('60', $city) .           // Merchant City
            $this->tlv('62', $this->tlv('05', $txid)); // Additional Data

        return $payload . $this->tlv('63', $this->crc16($payload . '6304'));
    }

    private function tlv(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private function crc16(string $str): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($str); $i++) {
            $crc ^= ord($str[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : $crc << 1;
            }
        }
        return strtoupper(sprintf('%04X', $crc & 0xFFFF));
    }
}
```

---

## Rotas de download

```php
// Em CardController:

public function downloadQrPng(string $slug): Response
{
    $card = Card::where('slug', $slug)->where('is_active', true)->firstOrFail();
    $png  = app(QrCodeService::class)->forCard($card, 'png');
    $name = 'qr-' . $card->slug . '.png';

    return response($png, 200, [
        'Content-Type'        => 'image/png',
        'Content-Disposition' => "attachment; filename=\"{$name}\"",
    ]);
}

public function downloadQrSvg(string $slug): Response
{
    $card = Card::where('slug', $slug)->where('is_active', true)->firstOrFail();
    $svg  = app(QrCodeService::class)->forCard($card, 'svg');
    $name = 'qr-' . $card->slug . '.svg';

    return response($svg, 200, [
        'Content-Type'        => 'image/svg+xml',
        'Content-Disposition' => "attachment; filename=\"{$name}\"",
    ]);
}
```

### Rotas web

```php
Route::get('/u/{slug}/qr.png', [CardController::class, 'downloadQrPng'])->name('card.qr.png');
Route::get('/u/{slug}/qr.svg', [CardController::class, 'downloadQrSvg'])->name('card.qr.svg');
```

---

## Modal de compartilhamento (painel)

```blade
{{-- Componente Alpine.js — incluir no layout app.blade.php ou na view do dashboard --}}
<div x-data="{ open: false, copied: false }"
     @open-share-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">

  <div @click.outside="open = false"
       x-transition:enter="transition duration-200 ease-out"
       x-transition:enter-start="translate-y-4 opacity-0"
       x-transition:enter-end="translate-y-0 opacity-100"
       class="bg-white rounded-[14px] w-full max-w-[360px] overflow-hidden shadow-2xl">

    {{-- Header do modal --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-[#E0E0DE]">
      <div class="flex items-center gap-2 text-[14px] font-semibold text-[#222]">
        <svg data-lucide="share-2" class="w-4 h-4 text-[#003049]"></svg>
        Compartilhar cartão
      </div>
      <button @click="open = false" class="text-[#888] hover:text-[#222]">
        <svg data-lucide="x" class="w-4 h-4"></svg>
      </button>
    </div>

    {{-- Body --}}
    <div class="p-5 flex flex-col items-center gap-4">

      {{-- QR Code --}}
      <div class="p-3 border border-[#E0E0DE] rounded-[10px]">
        {!! app(\App\Services\QrCodeService::class)->forCard(auth()->user()->card) !!}
      </div>

      {{-- URL com botão copiar --}}
      <div class="w-full flex items-center gap-2 bg-[#F5F5F3] rounded-[8px] px-3 py-2.5">
        <span class="flex-1 text-[12px] font-mono text-[#003049] truncate">
          {{ url('/u/' . auth()->user()->card->slug) }}
        </span>
        <button @click="navigator.clipboard.writeText('{{ url('/u/' . auth()->user()->card->slug) }}');
                        copied = true; setTimeout(() => copied = false, 2000)"
                class="flex items-center gap-1 text-[11px] font-medium transition-colors"
                :class="copied ? 'text-[#16A34A]' : 'text-[#003049]'">
          <svg x-show="!copied" data-lucide="copy" class="w-[14px] h-[14px]"></svg>
          <svg x-show="copied" data-lucide="check" class="w-[14px] h-[14px]"></svg>
          <span x-text="copied ? 'Copiado!' : 'Copiar'"></span>
        </button>
      </div>

      {{-- Botões de download --}}
      <div class="flex gap-2 w-full">
        <a href="{{ route('card.qr.png', auth()->user()->card->slug) }}"
           class="flex-1 flex items-center justify-center gap-1.5
                  border border-[#003049] text-[#003049] rounded-[8px] py-2
                  text-[12px] font-medium no-underline">
          <svg data-lucide="download" class="w-[14px] h-[14px]"></svg>
          Baixar PNG
        </a>
        <a href="{{ route('card.qr.svg', auth()->user()->card->slug) }}"
           class="flex-1 flex items-center justify-center gap-1.5
                  border border-[#003049] text-[#003049] rounded-[8px] py-2
                  text-[12px] font-medium no-underline">
          <svg data-lucide="download" class="w-[14px] h-[14px]"></svg>
          Baixar SVG
        </a>
      </div>
    </div>
  </div>
</div>
```

---

## Open Graph para preview no WhatsApp

```blade
{{-- Em layouts/card.blade.php, no <head> --}}
<title>{{ $card->display_name }} · Card</title>
<meta name="description" content="{{ Str::limit($card->bio ?? $card->title, 160) }}">

{{-- Open Graph --}}
<meta property="og:type"        content="profile">
<meta property="og:title"       content="{{ $card->display_name }}">
<meta property="og:description" content="{{ Str::limit($card->bio ?? $card->title, 160) }}">
<meta property="og:url"         content="{{ url('/u/' . $card->slug) }}">
<meta property="og:image"       content="{{ $card->profile_photo
    ? Storage::url($card->profile_photo)
    : asset('images/og-default.png') }}">
<meta property="og:image:width"  content="400">
<meta property="og:image:height" content="400">
<meta property="og:locale"       content="pt_BR">

{{-- Twitter Card --}}
<meta name="twitter:card"        content="summary">
<meta name="twitter:title"       content="{{ $card->display_name }}">
<meta name="twitter:description" content="{{ Str::limit($card->bio ?? $card->title, 160) }}">
<meta name="twitter:image"       content="{{ $card->profile_photo
    ? Storage::url($card->profile_photo)
    : asset('images/og-default.png') }}">
```

---

## PIX no cartão público — modal

```blade
{{-- Modal PIX — controlado por Alpine.js --}}
<div x-data="{ open: false }" id="pix-modal-wrapper">
  <div x-show="open"
       @click.self="open = false"
       class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center">
    <div class="bg-white rounded-t-[18px] w-full max-w-[400px] p-6 flex flex-col items-center gap-4">
      <p class="text-[14px] font-semibold text-[#003049]">
        Pagar via PIX
      </p>
      <img src="{{ route('card.pix-qr', $card->slug) }}"
           alt="QR Code PIX" class="w-48 h-48 rounded-[8px]">
      <p class="text-[12px] text-[#888] text-center">
        Abra o app do seu banco, acesse a opção PIX e escaneie o QR Code.
      </p>
      <p class="text-[11px] font-mono text-[#003049] break-all text-center">
        {{ $pixLink->url }}
      </p>
      <button @click="open = false"
              class="w-full border border-[#E0E0DE] rounded-[10px] py-2.5 text-[13px] text-[#888]">
        Fechar
      </button>
    </div>
  </div>
  <button @click="open = true" ...>Pagar via PIX</button>
</div>
```

---

## Checklist de entrega (T-066 a T-071)

- [ ] `QrCodeService::forCard` gerando SVG e PNG corretamente
- [ ] `QrCodeService::forPix` gerando payload EMV/BR Code válido
- [ ] Rotas de download QR PNG e SVG funcionando
- [ ] Modal de compartilhamento no painel com QR inline (SVG)
- [ ] Botão "Copiar link" com feedback visual (Alpine.js)
- [ ] Downloads PNG e SVG no modal
- [ ] Open Graph tags completas no `<head>` do cartão público
- [ ] Preview correto ao colar link no WhatsApp
- [ ] Teste: gerar QR, baixar PNG, escanear com celular e validar link
