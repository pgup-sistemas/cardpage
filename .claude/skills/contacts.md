# Skill: contacts
> Módulo M-04 · Contatos e vCard
> Leia também: CLAUDE.md · docs/design-system.md seções 3.3, 5.1

---

## Contexto do módulo

Gerencia os dados de contato do titular (telefone, e-mail, endereço, site).
Gera arquivo vCard 3.0 (.vcf) para download.

---

## Campos de contato em `cards`

Os campos já estão na tabela `cards` (ver docs/arquitetura.md):
- `contact_phone` — formatado com DDI+DDD (ex: `+55 69 99999-9999`)
- `contact_email`
- `address`
- `website`

---

## Editor de contatos (painel)

```php
// app/Livewire/Card/ContactEditor.php
class ContactEditor extends Component
{
    public Card $card;

    #[Validate('nullable|string|max:20')]
    public string $contact_phone = '';

    #[Validate('nullable|email|max:100')]
    public string $contact_email = '';

    #[Validate('nullable|string|max:200')]
    public string $address = '';

    #[Validate('nullable|url|max:200')]
    public string $website = '';

    public function mount(): void
    {
        $this->card          = auth()->user()->card;
        $this->contact_phone = $this->card->contact_phone ?? '';
        $this->contact_email = $this->card->contact_email ?? '';
        $this->address       = $this->card->address ?? '';
        $this->website       = $this->card->website ?? '';
    }

    public function save(): void
    {
        $this->validate();
        $this->card->update([
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'address'       => $this->address,
            'website'       => $this->website,
        ]);
        session()->flash('sucesso', 'Contatos atualizados.');
    }

    public function render(): View
    {
        return view('livewire.card.contact-editor');
    }
}
```

### View do ContactEditor

```blade
<div>
  <div class="flex items-center justify-between px-4 py-3.5 bg-white border-b border-[#E0E0DE]">
    <div class="flex items-center gap-2 text-[14px] font-semibold text-[#222]">
      <svg data-lucide="phone" class="w-4 h-4 text-[#003049]"></svg>
      Contatos
    </div>
    <button wire:click="save"
            class="flex items-center gap-1.5 bg-[#D62828] text-white rounded-[8px] px-2.5 py-[5px] text-[12px] font-medium">
      <svg data-lucide="save" class="w-[14px] h-[14px]"></svg>
      Salvar
    </button>
  </div>

  <div class="p-4 flex flex-col gap-3">
    @if(session('sucesso'))
    <div class="flex items-center gap-2 bg-[#D1FAE5] border border-[#6EE7B7] rounded-[8px] p-3">
      <svg data-lucide="check-circle" class="w-4 h-4 text-[#065F46]"></svg>
      <p class="text-[12px] text-[#065F46]">{{ session('sucesso') }}</p>
    </div>
    @endif

    <div class="flex flex-col gap-1">
      <label class="text-[11px] font-medium text-[#666]">
        Telefone / WhatsApp
      </label>
      <div class="flex items-center border border-[#ccc] rounded-[8px] overflow-hidden bg-[#FAFAF9]">
        <div class="px-3 py-[8px] border-r border-[#ccc] flex-shrink-0">
          <svg data-lucide="phone" class="w-4 h-4 text-[#888]"></svg>
        </div>
        <input wire:model="contact_phone" type="tel"
               placeholder="+55 69 99999-9999"
               class="flex-1 px-3 py-[8px] text-[13px] bg-[#FAFAF9] focus:outline-none">
      </div>
      <p class="text-[11px] text-[#888]">Inclua DDI e DDD. Usado no link do WhatsApp.</p>
      @error('contact_phone')<p class="text-[11px] text-[#D62828]">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-[11px] font-medium text-[#666]">E-mail de contato</label>
      <div class="flex items-center border border-[#ccc] rounded-[8px] overflow-hidden bg-[#FAFAF9]">
        <div class="px-3 py-[8px] border-r border-[#ccc] flex-shrink-0">
          <svg data-lucide="mail" class="w-4 h-4 text-[#888]"></svg>
        </div>
        <input wire:model="contact_email" type="email" placeholder="seu@email.com"
               class="flex-1 px-3 py-[8px] text-[13px] bg-[#FAFAF9] focus:outline-none">
      </div>
      @error('contact_email')<p class="text-[11px] text-[#D62828]">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-[11px] font-medium text-[#666]">Endereço</label>
      <div class="flex items-center border border-[#ccc] rounded-[8px] overflow-hidden bg-[#FAFAF9]">
        <div class="px-3 py-[8px] border-r border-[#ccc] flex-shrink-0">
          <svg data-lucide="map-pin" class="w-4 h-4 text-[#888]"></svg>
        </div>
        <input wire:model="address" type="text" placeholder="Rua, Bairro — Cidade, UF"
               class="flex-1 px-3 py-[8px] text-[13px] bg-[#FAFAF9] focus:outline-none">
      </div>
      @error('address')<p class="text-[11px] text-[#D62828]">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-[11px] font-medium text-[#666]">Site / portfólio</label>
      <div class="flex items-center border border-[#ccc] rounded-[8px] overflow-hidden bg-[#FAFAF9]">
        <div class="px-3 py-[8px] border-r border-[#ccc] flex-shrink-0">
          <svg data-lucide="globe" class="w-4 h-4 text-[#888]"></svg>
        </div>
        <input wire:model="website" type="url" placeholder="https://meusite.com.br"
               class="flex-1 px-3 py-[8px] text-[13px] bg-[#FAFAF9] focus:outline-none">
      </div>
      @error('website')<p class="text-[11px] text-[#D62828]">{{ $message }}</p>@enderror
    </div>
  </div>
</div>
```

---

## VCardService — geração do .vcf

```php
// app/Services/VCardService.php
class VCardService
{
    public function generate(Card $card): string
    {
        $photo = '';
        if ($card->profile_photo && Storage::exists($card->profile_photo)) {
            $data  = base64_encode(Storage::get($card->profile_photo));
            $mime  = Storage::mimeType($card->profile_photo) ?? 'image/jpeg';
            $type  = strtoupper(str_replace('image/', '', $mime));
            $photo = "PHOTO;ENCODING=b;TYPE={$type}:{$data}\r\n";
        }

        $phone = preg_replace('/\D/', '', $card->contact_phone ?? '');

        return implode("\r\n", array_filter([
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:' . $this->escape($card->display_name),
            $card->title   ? 'TITLE:' . $this->escape($card->title)   : null,
            $card->company ? 'ORG:' . $this->escape($card->company)   : null,
            $phone         ? "TEL;TYPE=CELL:{$phone}"                  : null,
            $card->contact_email ? 'EMAIL:' . $card->contact_email    : null,
            $card->address ? 'ADR:;;' . $this->escape($card->address) . ';;;;' : null,
            $card->website ? 'URL:' . $card->website                  : null,
            rtrim($photo, "\r\n"),
            'NOTE:Cartão via card.app/u/' . $card->slug,
            'END:VCARD',
        ])) . "\r\n";
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', '\\'], ['\\,', '\\;', '\\\\'], $value);
    }
}
```

### Rota e controller do download

```php
// No CardController:
public function downloadVcf(string $slug): Response
{
    $card = Card::where('slug', $slug)->where('is_active', true)->firstOrFail();
    $vcf  = app(VCardService::class)->generate($card);
    $name = Str::slug($card->display_name) . '.vcf';

    return response($vcf, 200, [
        'Content-Type'        => 'text/vcard; charset=utf-8',
        'Content-Disposition' => "attachment; filename=\"{$name}\"",
    ]);
}
```

---

## Bloco de contatos no cartão público

```blade
{{-- Em card/show.blade.php --}}
@if($card->contact_phone || $card->contact_email || $card->address || $card->website)
<div class="px-[18px] py-[14px] flex flex-col gap-2.5">

  @if($card->contact_phone)
  @php $phone = preg_replace('/\D/', '', $card->contact_phone); @endphp
  <div class="flex items-center gap-2.5">
    <svg data-lucide="phone" class="w-4 h-4 flex-shrink-0" style="color: var(--card-primary)"></svg>
    <div class="flex-1">
      <a href="tel:+{{ $phone }}" class="text-[13px] text-[#52514E] no-underline">
        {{ $card->contact_phone }}
      </a>
    </div>
    <a href="https://wa.me/{{ $phone }}" target="_blank" rel="noopener"
       class="flex items-center gap-1 text-[11px] font-medium no-underline
              px-2 py-1 rounded-[6px] bg-[#D1FAE5] text-[#065F46]">
      <svg data-lucide="message-circle" class="w-3 h-3"></svg>
      WhatsApp
    </a>
  </div>
  @endif

  @if($card->contact_email)
  <div class="flex items-center gap-2.5">
    <svg data-lucide="mail" class="w-4 h-4 flex-shrink-0" style="color: var(--card-primary)"></svg>
    <a href="mailto:{{ $card->contact_email }}" class="text-[13px] text-[#52514E] no-underline truncate">
      {{ $card->contact_email }}
    </a>
  </div>
  @endif

  @if($card->address)
  <div class="flex items-center gap-2.5">
    <svg data-lucide="map-pin" class="w-4 h-4 flex-shrink-0" style="color: var(--card-primary)"></svg>
    <a href="https://maps.google.com/?q={{ urlencode($card->address) }}" target="_blank" rel="noopener"
       class="text-[13px] text-[#52514E] no-underline">
      {{ $card->address }}
    </a>
  </div>
  @endif

  @if($card->website)
  <div class="flex items-center gap-2.5">
    <svg data-lucide="globe" class="w-4 h-4 flex-shrink-0" style="color: var(--card-primary)"></svg>
    <a href="{{ $card->website }}" target="_blank" rel="noopener"
       class="text-[13px] text-[#52514E] no-underline truncate">
      {{ $card->website }}
    </a>
  </div>
  @endif

</div>

{{-- Botão salvar contato (vCard) --}}
<div class="px-[18px] pb-[14px]">
  <a href="{{ route('card.vcf', $card->slug) }}"
     class="flex items-center justify-center gap-2.5 w-full px-3.5 py-[10px]
            rounded-[10px] text-[13px] font-medium no-underline
            border-[1.5px]"
     style="border-color: var(--card-primary); color: var(--card-primary)">
    <svg data-lucide="user-plus" class="w-4 h-4"></svg>
    Salvar contato
  </a>
</div>

<hr class="border-0 border-t border-[#E5E5E3] mx-[18px]">
@endif
```

---

## Checklist de entrega (T-051 a T-056)

- [ ] Livewire `ContactEditor` com todos os campos e validação
- [ ] `VCardService::generate` produzindo vCard 3.0 válido com foto
- [ ] Rota `/u/{slug}/vcf` retornando download correto
- [ ] Botão "Salvar contato" no cartão público
- [ ] Link WhatsApp formatado com número limpo (só dígitos)
- [ ] Link Google Maps para endereço
- [ ] Teste: baixar .vcf e importar no contatos do celular iOS/Android
