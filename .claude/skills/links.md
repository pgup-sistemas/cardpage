# Skill: links
> Módulo M-03 · Links e Redes Sociais
> Leia também: CLAUDE.md · docs/design-system.md seções 3.3, 5.6, 5.10

---

## Contexto do módulo

Gerencia links customizados, redes sociais e PIX.
O drag-and-drop usa **SortableJS** via NPM.
Detecção de rede social é automática via `SocialLinkService`.

```bash
npm install sortablejs
```

---

## Migration card_links

Ver docs/arquitetura.md seção 2 — já incluída.
`type` enum: `social | custom | pix | schedule`

---

## SocialLinkService — detecção de ícone

```php
// app/Services/SocialLinkService.php
class SocialLinkService
{
    public function detectIcon(string $url): string
    {
        return match(true) {
            str_contains($url, 'instagram.com')  => 'instagram',
            str_contains($url, 'linkedin.com')   => 'linkedin',
            str_contains($url, 'youtube.com')    => 'youtube',
            str_contains($url, 'tiktok.com')     => 'music',
            str_contains($url, 'twitter.com'),
            str_contains($url, 'x.com')          => 'twitter',
            str_contains($url, 'facebook.com')   => 'facebook',
            str_contains($url, 'telegram.me'),
            str_contains($url, 't.me')           => 'send',
            str_contains($url, 'pinterest.com')  => 'image',
            str_contains($url, 'spotify.com')    => 'music-2',
            str_contains($url, 'wa.me'),
            str_contains($url, 'whatsapp.com')   => 'message-circle',
            str_contains($url, 'github.com')     => 'github',
            default                              => 'link',
        };
    }

    public function detectType(string $url): string
    {
        $socialDomains = ['instagram.com', 'linkedin.com', 'youtube.com', 'tiktok.com',
                          'twitter.com', 'x.com', 'facebook.com', 'telegram.me', 't.me',
                          'pinterest.com', 'spotify.com', 'wa.me', 'whatsapp.com', 'github.com'];

        foreach ($socialDomains as $domain) {
            if (str_contains($url, $domain)) return 'social';
        }
        return 'custom';
    }
}
```

---

## Livewire: LinkManager

```php
// app/Livewire/Card/LinkManager.php
class LinkManager extends Component
{
    public Card $card;
    public bool $showForm = false;
    public string $newUrl   = '';
    public string $newLabel = '';
    public string $newIcon  = 'link';
    public string $newType  = 'custom';

    public function mount(): void
    {
        $this->card = auth()->user()->card;
    }

    #[Validate('required|url|max:500')]
    public function updatedNewUrl(string $value): void
    {
        $service = app(SocialLinkService::class);
        $this->newIcon = $service->detectIcon($value);
        $this->newType = $service->detectType($value);

        // Auto-preencher label com o domínio
        if (empty($this->newLabel)) {
            $host = parse_url($value, PHP_URL_HOST) ?? '';
            $this->newLabel = ucfirst(str_replace(['www.', '.com', '.net'], '', $host));
        }
    }

    public function addLink(): void
    {
        $planService = app(PlanService::class);

        if (!$planService->withinLimit(auth()->user(), 'links')) {
            $this->addError('limit', 'Você atingiu o limite de 5 links no plano Free. Faça upgrade para adicionar mais.');
            return;
        }

        $this->validate([
            'newUrl'   => 'required|url|max:500',
            'newLabel' => 'required|string|max:60',
        ]);

        $maxOrder = $this->card->links()->max('sort_order') ?? 0;

        $this->card->links()->create([
            'type'       => $this->newType,
            'label'      => $this->newLabel,
            'url'        => $this->newUrl,
            'icon'       => $this->newIcon,
            'is_active'  => true,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->reset(['newUrl', 'newLabel', 'newIcon', 'newType', 'showForm']);
        $this->dispatch('link-added');
    }

    public function toggleLink(int $id): void
    {
        $link = $this->card->links()->findOrFail($id);
        $link->update(['is_active' => !$link->is_active]);
    }

    public function deleteLink(int $id): void
    {
        $this->card->links()->findOrFail($id)->delete();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $index => $id) {
            $this->card->links()->where('id', $id)->update(['sort_order' => $index]);
        }
    }

    public function render(): View
    {
        $links = $this->card->links()->orderBy('sort_order')->get();
        $count = $links->count();
        $limit = auth()->user()->plan === 'pro' ? PHP_INT_MAX : 5;

        return view('livewire.card.link-manager', compact('links', 'count', 'limit'));
    }
}
```

### View do LinkManager

```blade
{{-- resources/views/livewire/card/link-manager.blade.php --}}
<div>
  {{-- Header --}}
  <div class="flex items-center justify-between px-4 py-3.5 bg-white border-b border-[#E0E0DE]">
    <div class="flex items-center gap-2 text-[14px] font-semibold text-[#222]">
      <svg data-lucide="link" class="w-4 h-4 text-[#003049]"></svg>
      Links e redes sociais
    </div>
    <button wire:click="$set('showForm', true)"
            class="flex items-center gap-1.5 bg-[#D62828] text-white rounded-[8px] px-2.5 py-[5px] text-[12px] font-medium">
      <svg data-lucide="plus" class="w-[14px] h-[14px]"></svg>
      Adicionar
    </button>
  </div>

  <div class="p-4 flex flex-col gap-3">

    {{-- Indicador de limite (Free) --}}
    @if(auth()->user()->plan !== 'pro')
    <div class="flex items-center justify-between text-[11px] text-[#888]">
      <span>{{ $count }} de 5 links</span>
      @if($count >= 5)
        <a href="{{ route('dashboard.plan') }}" class="text-[#7C3AED] font-medium">Fazer upgrade →</a>
      @endif
    </div>
    <div class="h-1 bg-[#E0E0DE] rounded-full overflow-hidden">
      <div class="h-full bg-[#003049] rounded-full transition-all"
           style="width: {{ min(($count / 5) * 100, 100) }}%"></div>
    </div>
    @endif

    @error('limit')
    <div class="flex items-center gap-2 bg-[#FEF3C7] border border-[#FCD34D] rounded-[8px] p-3">
      <svg data-lucide="alert-triangle" class="w-4 h-4 text-[#D97706] flex-shrink-0"></svg>
      <p class="text-[12px] text-[#92400E]">{{ $message }}</p>
    </div>
    @enderror

    {{-- Formulário de adição --}}
    @if($showForm)
    <div class="bg-[#F5F5F3] border border-[#E0E0DE] rounded-[10px] p-3.5 flex flex-col gap-2.5">
      <p class="text-[12px] font-semibold text-[#222]">Novo link</p>

      <div class="flex flex-col gap-1">
        <label class="text-[11px] font-medium text-[#666]">URL</label>
        <input wire:model.live="newUrl" type="url" placeholder="https://..."
               class="w-full border border-[#ccc] rounded-[8px] px-[10px] py-[8px]
                      text-[13px] bg-[#FAFAF9] focus:outline-none focus:border-[#003049]">
        @error('newUrl')<p class="text-[11px] text-[#D62828]">{{ $message }}</p>@enderror
      </div>

      <div class="flex items-center gap-2">
        {{-- Preview do ícone detectado --}}
        <div class="w-8 h-8 rounded-[8px] bg-[#003049] flex items-center justify-center flex-shrink-0">
          <svg data-lucide="{{ $newIcon }}" class="w-4 h-4 text-[#EAE2B7]"></svg>
        </div>
        <div class="flex-1 flex flex-col gap-1">
          <label class="text-[11px] font-medium text-[#666]">Label</label>
          <input wire:model="newLabel" type="text" placeholder="Ex: Meu portfólio"
                 class="w-full border border-[#ccc] rounded-[8px] px-[10px] py-[8px]
                        text-[13px] bg-[#FAFAF9] focus:outline-none focus:border-[#003049]">
        </div>
      </div>

      <div class="flex gap-2 mt-1">
        <button wire:click="addLink"
                class="flex-1 bg-[#003049] text-white text-[13px] font-medium rounded-[8px] py-2">
          Adicionar link
        </button>
        <button wire:click="$set('showForm', false)"
                class="px-3 border border-[#ccc] text-[#888] text-[13px] rounded-[8px] py-2">
          Cancelar
        </button>
      </div>
    </div>
    @endif

    {{-- Lista de links (drag-and-drop via SortableJS) --}}
    <div id="sortable-links">
      @forelse($links as $link)
      <div data-id="{{ $link->id }}"
           class="flex items-center gap-2.5 p-2.5 bg-[#FAFAF9] border border-[#E0E0DE]
                  rounded-[10px] mb-2 {{ !$link->is_active ? 'opacity-50' : '' }}">
        <button class="cursor-grab touch-none text-[#aaa] flex-shrink-0">
          <svg data-lucide="grip-vertical" class="w-4 h-4"></svg>
        </button>
        <div class="w-8 h-8 rounded-[8px] bg-[#003049] flex items-center justify-center flex-shrink-0">
          <svg data-lucide="{{ $link->icon }}" class="w-4 h-4 text-[#EAE2B7]"></svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] font-medium text-[#222] truncate">{{ $link->label }}</p>
          <p class="text-[11px] text-[#888] truncate">{{ $link->url }}</p>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <button wire:click="toggleLink({{ $link->id }})">
            <svg data-lucide="{{ $link->is_active ? 'toggle-right' : 'toggle-left' }}"
                 class="w-5 h-5 {{ $link->is_active ? 'text-[#003049]' : 'text-[#ccc]' }}"></svg>
          </button>
          <button wire:click="deleteLink({{ $link->id }})"
                  wire:confirm="Excluir este link?"
                  class="text-[#aaa] hover:text-[#D62828] transition-colors">
            <svg data-lucide="trash-2" class="w-4 h-4"></svg>
          </button>
        </div>
      </div>
      @empty
      <div class="flex flex-col items-center gap-3 py-10 text-center">
        <div class="w-12 h-12 rounded-full bg-[#EBEBEA] flex items-center justify-center">
          <svg data-lucide="link" class="w-6 h-6 text-[#aaa]"></svg>
        </div>
        <p class="text-[13px] font-medium text-[#666]">Nenhum link ainda</p>
        <p class="text-[12px] text-[#aaa]">Adicione links de redes sociais<br>ou personalizados</p>
      </div>
      @endforelse
    </div>

  </div>
</div>
```

### JavaScript do drag-and-drop

```js
// resources/js/sortable.js
import Sortable from 'sortablejs';

document.addEventListener('livewire:initialized', () => {
    const el = document.getElementById('sortable-links');
    if (!el) return;

    Sortable.create(el, {
        handle: '[data-lucide="grip-vertical"]',
        animation: 150,
        onEnd({ to }) {
            const order = [...to.querySelectorAll('[data-id]')]
                .map(el => el.dataset.id);
            Livewire.dispatch('reorder-links', { order });
        }
    });
});
```

---

## Exibição dos links no cartão público

```blade
{{-- Em card/show.blade.php --}}
@php $links = $card->links()->where('is_active', true)->orderBy('sort_order')->get(); @endphp

@if($links->isNotEmpty())
<div class="px-[18px] py-[14px] flex flex-col gap-2">
  @foreach($links as $link)
    @if($link->type === 'pix')
      {{-- Botão PIX especial --}}
      <button onclick="document.getElementById('pix-modal').classList.remove('hidden')"
              class="flex items-center gap-2.5 w-full px-3.5 py-[11px] rounded-[10px]
                     text-[13px] font-medium bg-[#F77F00] text-white">
        <svg data-lucide="qr-code" class="w-4 h-4 flex-shrink-0"></svg>
        <span class="flex-1 text-left">{{ $link->label }}</span>
        <svg data-lucide="arrow-right" class="w-[14px] h-[14px] opacity-70"></svg>
      </button>
    @elseif($loop->first)
      {{-- Primeiro link: CTA primário na cor do botão --}}
      <a href="{{ $link->url }}" target="_blank" rel="noopener"
         class="flex items-center gap-2.5 w-full px-3.5 py-[11px] rounded-[10px]
                text-[13px] font-medium text-white no-underline"
         style="background-color: var(--card-button)">
        <svg data-lucide="{{ $link->icon }}" class="w-4 h-4 flex-shrink-0"></svg>
        <span class="flex-1 text-left">{{ $link->label }}</span>
        <svg data-lucide="arrow-right" class="w-[14px] h-[14px] opacity-70"></svg>
      </a>
    @else
      {{-- Demais links: outline --}}
      <a href="{{ $link->url }}" target="_blank" rel="noopener"
         class="flex items-center gap-2.5 w-full px-3.5 py-[11px] rounded-[10px]
                text-[13px] font-medium bg-transparent no-underline border-[1.5px]"
         style="border-color: var(--card-primary); color: var(--card-primary)">
        <svg data-lucide="{{ $link->icon }}" class="w-4 h-4 flex-shrink-0"></svg>
        <span class="flex-1 text-left">{{ $link->label }}</span>
        <svg data-lucide="arrow-right" class="w-[14px] h-[14px] opacity-70"></svg>
      </a>
    @endif
  @endforeach
</div>
<hr class="border-0 border-t border-[#E5E5E3] mx-[18px]">
@endif
```

---

## Checklist de entrega (T-042 a T-050)

- [ ] Migration `card_links` criada e rodada
- [ ] Model `CardLink` com fillable, casts e scope `active()`
- [ ] `SocialLinkService::detectIcon` e `detectType` com todos os domínios mapeados
- [ ] Livewire `LinkManager` com CRUD funcional
- [ ] Detecção automática de ícone ao digitar URL (wire:model.live)
- [ ] SortableJS integrado — reorder persistido via Livewire
- [ ] Toggle ativo/inativo funcional
- [ ] Validação de limite 5 links no Free com mensagem de upgrade
- [ ] Links exibidos no cartão público: primeiro como CTA, demais como outline
- [ ] PIX como botão laranja especial
- [ ] Ícones Lucide corretos para cada rede social
- [ ] Teste: criar 6 links no Free → verificar bloqueio no 6º
