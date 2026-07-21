# Skill M-11 — Cardápio de Serviços + PIX Dinâmico
> Card SaaS · PageUp Sistemas · Porto Velho, RO
> Versão: 1.0 · Data: 2026-07-20 · Status: PRONTO PARA IMPLEMENTAÇÃO

---

## 1. Visão geral do módulo

O **M-11** adiciona um "cardápio de serviços" ao cartão público. O titular cadastra seus serviços com preço no painel. O visitante vê os serviços, toca em um e recebe um modal com QR Code PIX e o valor já preenchido — sem digitar nada, sem sair do cartão, sem conta em banco integrado.

### Por que não precisa de Efi Bank

O PIX com valor preenchido é gerado no padrão **EMV BR Code** do Banco Central — um payload texto puro que qualquer app bancário lê. Não há integração com banco, não há taxa por transação, não há confirmação automática de pagamento. O Efi Bank permanece exclusivamente para billing do SaaS (M-08).

### Casos de uso reais

| Perfil | Exemplo de uso |
|---|---|
| Barbeiro | Corte R$45 / Barba R$30 / Combo R$70 — cliente escolhe e paga |
| Psicólogo | Sessão 1h R$200 — link enviado pelo WhatsApp, cliente paga sem app extra |
| Freelancer | Consultoria R$350 — link direto `/u/joao/pagar/consultoria` na bio |
| Pequeno comércio | Produtos com preço fixo — cartão vira mini PDV sem maquininha |

---

## 2. Regras de negócio

- **Free:** até 3 serviços (sem PIX dinâmico por valor — apenas chave PIX estática existente)
- **Pro:** até 20 serviços com PIX EMV por valor; link direto de pagamento; "Pix copia e cola"
- Serviço inativo não aparece no cartão público
- Valor mínimo: R$ 1,00 · Máximo: R$ 99.999,99
- Nome do serviço: máximo 60 caracteres
- Descrição: máximo 160 caracteres (opcional)
- O campo `pix_key` do cartão deve estar preenchido — validar antes de exibir serviços
- Se o titular não tem chave PIX cadastrada, exibir aviso no painel e não mostrar serviços no cartão público

---

## 3. Migration

```php
// database/migrations/0011_create_card_services_table.php
Schema::create('card_services', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->cascadeOnDelete();
    $table->string('name', 60);
    $table->string('description', 160)->nullable();
    $table->decimal('price', 10, 2);
    $table->string('lucide_icon', 40)->default('tag');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['card_id', 'sort_order']);
    $table->index(['card_id', 'is_active']);
});
```

---

## 4. Model — CardService

```php
// app/Models/CardService.php
class CardService extends Model
{
    protected $fillable = [
        'card_id', 'name', 'description', 'price',
        'lucide_icon', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /** Valor formatado: R$ 1.500,00 */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}
```

### Relacionamento em Card.php

```php
// Adicionar em app/Models/Card.php
public function services(): HasMany
{
    return $this->hasMany(CardService::class)->orderBy('sort_order');
}
```

---

## 5. QrCodeService — geração do payload PIX EMV

O payload segue o padrão **BR Code / EMV QRCPS-MPM** definido pelo Banco Central (resolução BCB nº 1/2020).

```php
// Adicionar ao app/Services/QrCodeService.php

/**
 * Gera o payload "Pix copia e cola" (EMV BR Code).
 *
 * @param  string  $pixKey      Chave PIX (CPF, CNPJ, e-mail, telefone ou aleatória)
 * @param  float   $amount      Valor em reais (ex: 150.00)
 * @param  string  $merchantName Nome do recebedor — máximo 25 chars, ASCII sem acentos
 * @param  string  $city        Cidade — máximo 15 chars
 * @param  string  $txid        Identificador da transação — máximo 25 chars alfanumérico
 * @return string  Payload EMV pronto para exibir como QR Code ou texto
 */
public function pixPayload(
    string $pixKey,
    float  $amount,
    string $merchantName,
    string $city   = 'Brasil',
    string $txid   = '***'
): string {
    $merchantName = substr($this->toAscii($merchantName), 0, 25);
    $city         = substr($this->toAscii($city), 0, 15);
    $txid         = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 25) ?: '***';
    $amountStr    = number_format($amount, 2, '.', '');

    // Campo 26 — Merchant Account Information (GUI + chave PIX)
    $gui = $this->emvField('00', 'br.gov.bcb.pix');
    $key = $this->emvField('01', $pixKey);
    $mai = $this->emvField('26', $gui . $key);

    // Campo 62 — Additional Data (TxID)
    $txField      = $this->emvField('05', $txid);
    $additional   = $this->emvField('62', $txField);

    $payload =
        $this->emvField('00', '01')     // Payload Format Indicator
        . $mai                          // Merchant Account Info
        . $this->emvField('52', '0000') // Merchant Category Code
        . $this->emvField('53', '986')  // Currency (BRL)
        . $this->emvField('54', $amountStr) // Transaction Amount
        . $this->emvField('58', 'BR')   // Country Code
        . $this->emvField('59', $merchantName)
        . $this->emvField('60', $city)
        . $additional
        . '6304';                       // CRC16 tag sem valor (calculado abaixo)

    return $payload . $this->crc16($payload);
}

/** Formata um campo EMV: ID (2 chars) + comprimento (2 chars) + valor */
private function emvField(string $id, string $value): string
{
    return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

/** CRC16-CCITT-FALSE: poly 0x1021, init 0xFFFF */
private function crc16(string $payload): string
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $crc ^= (ord($payload[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
        }
    }
    return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
}

/** Remove acentos e caracteres não-ASCII (PIX aceita apenas ASCII básico) */
private function toAscii(string $text): string
{
    return preg_replace('/[^\x00-\x7F]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text));
}
```

---

## 6. Controller público

```php
// app/Http/Controllers/ServicePixController.php
class ServicePixController extends Controller
{
    public function __construct(private QrCodeService $qr) {}

    /**
     * Retorna o payload PIX e o QR Code SVG para um serviço específico.
     * Usado via fetch() no modal do cartão público.
     */
    public function payload(Card $card, CardService $service): JsonResponse
    {
        abort_unless($card->is_active && $service->card_id === $card->id && $service->is_active, 404);
        abort_unless($card->pix_key, 422, 'Titular sem chave PIX cadastrada.');

        $payload = $this->qr->pixPayload(
            pixKey:       $card->pix_key,
            amount:       (float) $service->price,
            merchantName: $card->display_name,
            city:         $card->address
                ? (explode(',', $card->address)[0] ?? 'Brasil')
                : 'Brasil',
            txid:         'SRV' . $service->id,
        );

        return response()->json([
            'payload'   => $payload,
            'qr_svg'    => $this->qr->svg($payload),       // SVG sem borda, fundo branco
            'formatted' => $service->formatted_price,
            'name'      => $service->name,
        ]);
    }

    /**
     * Página de pagamento direta: /u/{slug}/pagar/{service}
     * Carrega o cartão e abre o modal de pagamento já expandido.
     * Útil para links enviados por WhatsApp.
     */
    public function show(string $slug, CardService $service): View
    {
        $card = Card::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($service->card_id === $card->id && $service->is_active, 404);

        // Reutiliza a view do cartão com flag para abrir modal automaticamente
        return view('card.show', [
            'card'          => $card->load(['links', 'photos', 'schedule', 'services']),
            'qrSvg'         => app(QrCodeService::class)->svg(url("/u/{$card->slug}")),
            'autoOpenService' => $service->id,
        ]);
    }
}
```

---

## 7. Livewire — ServiceManager (painel)

```php
// app/Livewire/Card/ServiceManager.php
class ServiceManager extends Component
{
    public Card $card;

    #[Validate('required|string|max:60')]
    public string $name = '';

    #[Validate('nullable|string|max:160')]
    public string $description = '';

    #[Validate('required|numeric|min:1|max:99999.99')]
    public string $price = '';

    #[Validate('required|string|max:40')]
    public string $lucide_icon = 'tag';

    public bool $showForm = false;
    public ?int $editingId = null;

    public function mount(Card $card): void
    {
        $this->card = $card;
    }

    public function getServicesProperty(): Collection
    {
        return $this->card->services()->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $this->validate();
        $this->authorize('update', $this->card);

        $limit = $this->card->user->isPro() ? 20 : 3;
        if (!$this->editingId && $this->card->services()->count() >= $limit) {
            $this->addError('name', "Limite de {$limit} serviços atingido.");
            return;
        }

        $data = [
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'price'       => (float) str_replace(',', '.', $this->price),
            'lucide_icon' => $this->lucide_icon,
        ];

        if ($this->editingId) {
            $this->card->services()->findOrFail($this->editingId)->update($data);
        } else {
            $data['sort_order'] = $this->card->services()->max('sort_order') + 1;
            $this->card->services()->create($data);
        }

        $this->reset(['name', 'description', 'price', 'lucide_icon', 'showForm', 'editingId']);
        $this->dispatch('service-saved');
    }

    public function edit(int $id): void
    {
        $service = $this->card->services()->findOrFail($id);
        $this->editingId   = $id;
        $this->name        = $service->name;
        $this->description = $service->description ?? '';
        $this->price       = number_format($service->price, 2, ',', '');
        $this->lucide_icon = $service->lucide_icon;
        $this->showForm    = true;
    }

    public function toggleActive(int $id): void
    {
        $service = $this->card->services()->findOrFail($id);
        $service->update(['is_active' => !$service->is_active]);
    }

    public function delete(int $id): void
    {
        $this->card->services()->findOrFail($id)->delete();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $i => $id) {
            $this->card->services()->where('id', $id)->update(['sort_order' => $i]);
        }
    }

    public function render(): View
    {
        return view('livewire.card.service-manager');
    }
}
```

---

## 8. Rotas

```php
// routes/web.php — adicionar junto das rotas públicas de cartão

// Payload JSON para modal (fetch assíncrono)
Route::get('/u/{card:slug}/servico/{service}/payload', [ServicePixController::class, 'payload'])
    ->name('card.service.payload');

// Página direta de pagamento (link externo / WhatsApp)
Route::get('/u/{card:slug}/pagar/{service}', [ServicePixController::class, 'show'])
    ->name('card.service.pay');
```

---

## 9. View — seção Serviços no cartão público (`show.blade.php`)

### CSS a adicionar em `@section('card_colors')`

```css
/* ── Serviços ── */
.svc-list { display: flex; flex-direction: column; gap: 0; }
.svc-item {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 16px; min-height: 56px;
    border-bottom: 1px solid var(--ui-border);
    cursor: pointer; -webkit-tap-highlight-color: transparent;
    transition: background .1s;
}
.svc-item:last-child { border-bottom: none; }
.svc-item:active { background: var(--ui-tap); }
.svc-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    background-color: var(--card-button);
    display: flex; align-items: center; justify-content: center;
}
.svc-info { flex: 1; min-width: 0; }
.svc-name { font-size: 13px; font-weight: 600; color: var(--ui-heading); }
.svc-desc { font-size: 11.5px; color: var(--ui-label); margin-top: 1px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.svc-price { font-size: 14px; font-weight: 700; color: var(--card-button);
             white-space: nowrap; }

/* ── Modal PIX ── */
.pix-modal-bg {
    display: none; position: fixed; inset: 0; z-index: 9000;
    background: rgba(0,0,0,0.52); align-items: flex-end; justify-content: center;
}
.pix-modal-bg.open { display: flex; }
.pix-modal {
    background: #fff; border-radius: 20px 20px 0 0;
    padding: 0 0 env(safe-area-inset-bottom, 12px);
    width: 100%; max-width: 440px;
    animation: slideUp .22s ease;
}
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.pix-modal-handle {
    width: 36px; height: 4px; border-radius: 2px; background: #e5e7eb;
    margin: 12px auto 4px;
}
.pix-modal-head { padding: 8px 20px 14px; border-bottom: 1px solid #f0f0f0; }
.pix-modal-title { font-size: 16px; font-weight: 700; color: #0d1117; }
.pix-modal-price { font-size: 26px; font-weight: 800; color: var(--card-button);
                   letter-spacing: -.5px; margin-top: 2px; }
.pix-modal-body { padding: 16px 20px; }
.pix-qr-wrap { display: flex; justify-content: center; padding: 8px 0 12px; }
.pix-qr-wrap svg { border-radius: 8px; }
.pix-copy-field {
    background: #f7f7f6; border: 1px solid var(--ui-border); border-radius: 10px;
    padding: 10px 12px; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;
}
.pix-copy-text { font-size: 11px; color: #6b7280; flex: 1;
                 overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pix-copy-btn {
    font-size: 11px; font-weight: 700; color: var(--card-button);
    background: none; border: none; cursor: pointer; flex-shrink: 0; padding: 4px 0;
    -webkit-tap-highlight-color: transparent;
}
.pix-action-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 14px; border-radius: 12px; border: none;
    font-size: 14px; font-weight: 700; cursor: pointer;
    background-color: var(--card-button); color: #fff;
    transition: opacity .15s; -webkit-tap-highlight-color: transparent;
}
.pix-action-btn:active { opacity: .82; }
.pix-feedback { display: none; text-align: center; font-size: 12px;
                color: #16a34a; font-weight: 600; margin-top: 10px; }
```

### Blade — seção no cartão público

```blade
{{-- ── SERVIÇOS / PIX DINÂMICO ── --}}
@if ($card->pix_key && $card->services->where('is_active', true)->isNotEmpty())
<div class="cs" x-data="{ open: true }">
    <button class="cs-head" type="button" @click="open = !open">
        <div class="cs-label">
            <i data-lucide="briefcase" style="width:13px;height:13px;color:var(--ui-icon);"></i>
            Serviços
        </div>
        <i data-lucide="chevron-down" class="cs-chevron"
           :style="open ? 'transform:rotate(180deg)' : ''"></i>
    </button>
    <div class="cs-body-flush" x-show="open"
         x-transition:enter="transition ease-out duration-180"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="svc-list">
            @foreach ($card->services->where('is_active', true) as $service)
            <div class="svc-item" onclick="nexosnOpenPix({{ $service->id }})">
                <div class="svc-icon">
                    <svg data-lucide="{{ $service->lucide_icon }}"
                         width="16" height="16" stroke="#fff"
                         stroke-width="2" fill="none"
                         stroke-linecap="round" stroke-linejoin="round"></svg>
                </div>
                <div class="svc-info">
                    <div class="svc-name">{{ $service->name }}</div>
                    @if ($service->description)
                    <div class="svc-desc">{{ $service->description }}</div>
                    @endif
                </div>
                <div class="svc-price">{{ $service->formatted_price }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Modal PIX --}}
<div id="pix-modal-bg" class="pix-modal-bg" onclick="if(event.target===this)nexosnClosePix()">
    <div class="pix-modal">
        <div class="pix-modal-handle"></div>
        <div class="pix-modal-head">
            <div class="pix-modal-title" id="pix-modal-name">Carregando…</div>
            <div class="pix-modal-price" id="pix-modal-price"></div>
        </div>
        <div class="pix-modal-body">
            <div class="pix-qr-wrap" id="pix-modal-qr">
                <div style="width:200px;height:200px;background:#f0f0ee;border-radius:8px;
                            display:flex;align-items:center;justify-content:center;">
                    <svg data-lucide="loader-2" width="28" height="28" stroke="#d1d5db"
                         stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"
                         style="animation:spin 1s linear infinite;"></svg>
                </div>
            </div>
            <div class="pix-copy-field">
                <span class="pix-copy-text" id="pix-copy-text">—</span>
                <button class="pix-copy-btn" onclick="nexosnCopyPix()">Copiar</button>
            </div>
            <button class="pix-action-btn" onclick="nexosnCopyPix()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Copiar código PIX
            </button>
            <div class="pix-feedback" id="pix-feedback">✓ Código copiado! Cole no seu app bancário.</div>
        </div>
    </div>
</div>
@endif
```

### JavaScript do modal

```js
@if ($card->pix_key && $card->services->where('is_active', true)->isNotEmpty())
@php $autoOpen = $autoOpenService ?? null; @endphp
<script>
@style('animation:spin 1s linear infinite')
const _pixRouteBase = '{{ url("/u/{$card->slug}/servico") }}';
let _pixPayload = '';

async function nexosnOpenPix(serviceId) {
    const bg   = document.getElementById('pix-modal-bg');
    const name = document.getElementById('pix-modal-name');
    const price = document.getElementById('pix-modal-price');
    const qr   = document.getElementById('pix-modal-qr');

    name.textContent  = 'Aguarde…';
    price.textContent = '';
    qr.innerHTML      = '<div style="width:200px;height:200px;background:#f0f0ee;border-radius:8px;display:flex;align-items:center;justify-content:center;"><svg data-lucide=\'loader-2\' width=\'28\' height=\'28\' stroke=\'#d1d5db\' stroke-width=\'2\' fill=\'none\' stroke-linecap=\'round\' stroke-linejoin=\'round\' style=\'animation:spin 1s linear infinite;\'></svg></div>';
    document.getElementById('pix-copy-text').textContent = '—';
    document.getElementById('pix-feedback').style.display = 'none';
    bg.classList.add('open');
    document.body.style.overflow = 'hidden';

    try {
        const res  = await fetch(`${_pixRouteBase}/${serviceId}/payload`);
        const data = await res.json();
        _pixPayload = data.payload;

        name.textContent              = data.name;
        price.textContent             = data.formatted;
        qr.innerHTML                  = data.qr_svg;
        document.getElementById('pix-copy-text').textContent = data.payload;
        if (window.lucide) lucide.createIcons({ el: qr });
    } catch {
        name.textContent = 'Erro ao carregar. Tente novamente.';
    }
}

function nexosnClosePix() {
    document.getElementById('pix-modal-bg').classList.remove('open');
    document.body.style.overflow = '';
}

function nexosnCopyPix() {
    if (!_pixPayload) return;
    navigator.clipboard.writeText(_pixPayload).then(() => {
        const fb = document.getElementById('pix-feedback');
        fb.style.display = 'block';
        setTimeout(() => fb.style.display = 'none', 4000);
    });
}

@if ($autoOpen)
document.addEventListener('DOMContentLoaded', () => nexosnOpenPix({{ $autoOpen }}));
@endif
</script>
@endif
```

---

## 10. View Livewire — painel (`service-manager.blade.php`)

### Estrutura da view

```
resources/views/livewire/card/service-manager.blade.php
```

A view deve:
- Listar serviços com ícone, nome, preço e toggles de ativo/inativo
- Botão "Adicionar serviço" → abre form inline (não modal, para evitar conflito com Alpine)
- Form: lucide_icon (select com preview), nome, descrição, preço (máscara R$)
- Se não tem `pix_key` → banner de aviso amarelo com link para editar
- Drag-and-drop para reordenar (SortableJS, igual ao LinkManager)
- Limite Pro: badge "3/3 serviços (Free)" com CTA de upgrade

### Estrutura de um item

```blade
<div class="..." wire:key="svc-{{ $service->id }}">
    <div class="clink-icon">
        <svg data-lucide="{{ $service->lucide_icon }}" ...></svg>
    </div>
    <div style="flex:1;">
        <div>{{ $service->name }}</div>
        <div>{{ $service->formatted_price }}</div>
    </div>
    <button wire:click="toggleActive({{ $service->id }})">...</button>
    <button wire:click="edit({{ $service->id }})">...</button>
    <button wire:click="delete({{ $service->id }})"
            wire:confirm="Excluir {{ $service->name }}?">...</button>
</div>
```

---

## 11. Checklist de entrega

### Backend
- [ ] Migration `0011_create_card_services_table` criada e executada
- [ ] Model `CardService` com `$fillable`, `$casts` e accessor `formatted_price`
- [ ] Relacionamento `services()` adicionado ao `Card.php`
- [ ] Método `pixPayload()` adicionado ao `QrCodeService.php`
- [ ] Métodos auxiliares `emvField()`, `crc16()`, `toAscii()` no `QrCodeService.php`
- [ ] `ServicePixController::payload()` retornando JSON correto
- [ ] `ServicePixController::show()` reutilizando `card.show` com `$autoOpenService`
- [ ] Rotas nomeadas adicionadas ao `web.php`
- [ ] `ServiceManager` Livewire com CRUD completo + validação de limite por plano

### Frontend
- [ ] Seção "Serviços" no `show.blade.php` usando classes `.cs` existentes
- [ ] CSS do modal `.pix-modal-*` adicionado em `@section('card_colors')`
- [ ] Modal com QR SVG + "Copiar código PIX" + feedback visual
- [ ] Auto-open via `$autoOpenService` quando acessado via link direto
- [ ] Ícones nos itens de serviço via `data-lucide` (Lucide createIcons já roda no card.js)
- [ ] View Livewire `service-manager.blade.php` com aviso de chave PIX ausente
- [ ] `@keyframes spin` adicionado ao CSS para o loading spinner

### Qualidade
- [ ] Testar payload EMV com validador online do Banco Central: https://pix.bcb.gov.br/qrcode/validacao
- [ ] Testar QR Code lido por Nubank, Itaú, Bradesco, Caixa, Banco do Brasil
- [ ] Testar "Pix copia e cola" copiado manualmente em app bancário
- [ ] Testar link direto `/u/slug/pagar/{service}` abrindo modal automaticamente
- [ ] Testar limite Free (3 serviços) e Pro (20 serviços)
- [ ] `php artisan test --filter=ServicePixTest`

---

## 12. Testes sugeridos

```php
// tests/Feature/ServicePixTest.php

it('gera payload EMV válido com valor', function () {
    $payload = app(QrCodeService::class)->pixPayload(
        pixKey: 'joao@email.com',
        amount: 150.00,
        merchantName: 'João Silva',
        city: 'Porto Velho',
        txid: 'SRV001'
    );

    expect($payload)->toStartWith('000201')
        ->toContain('br.gov.bcb.pix')
        ->toContain('joao@email.com')
        ->toContain('15000')   // valor em centavos sem separador
        ->toEndWith(app(QrCodeService::class)->crc16Test($payload)); // CRC bate
});

it('payload route retorna json com campos corretos', function () {
    $card    = Card::factory()->create(['pix_key' => '11999999999']);
    $service = CardService::factory()->for($card)->create(['price' => 45.00]);

    $this->get(route('card.service.payload', [$card->slug, $service]))
         ->assertOk()
         ->assertJsonStructure(['payload', 'qr_svg', 'formatted', 'name']);
});

it('link direto de pagamento abre com auto_open_service', function () {
    $card    = Card::factory()->create(['pix_key' => '11999999999', 'is_active' => true]);
    $service = CardService::factory()->for($card)->create(['is_active' => true]);

    $this->get(route('card.service.pay', [$card->slug, $service]))
         ->assertOk()
         ->assertSee("nexosnOpenPix({$service->id})");
});
```

---

## 13. Notas de design (preservar estilo do template)

- Usar classe `.cs` existente para a seção "Serviços" — mesma aparência das outras seções
- Ícone do serviço: quadrado 36px com `border-radius: 10px`, cor `var(--card-button)` — mesmo padrão do `.clink-icon`
- Preço em destaque com `var(--card-button)` — consistência com demais CTAs
- Modal bottom-sheet (sobe de baixo) — padrão mobile nativo, menos disruptivo que modal centrado
- Handle bar no topo do modal (linha cinza) — convenção universal de bottom-sheet
- Botão "Copiar código PIX" com cor `var(--card-button)` — não criar nova cor
- Feedback de cópia: texto verde `#16a34a` — mesma convenção do resto do cartão
- Nenhum ícone externo — usar `data-lucide` com `lucide.createIcons()` já inicializado
- Spinner de loading: `data-lucide="loader-2"` com `animation: spin`
- Não usar `position: fixed` em elementos internos do modal (o próprio `.pix-modal-bg` é fixed)
