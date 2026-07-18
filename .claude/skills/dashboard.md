# Skill: dashboard
> Módulo M-07 · Painel do Usuário
> Leia também: CLAUDE.md · docs/design-system.md seções 4.3 e 6.2

---

## Contexto do módulo

O painel é a área autenticada do titular. Stack: Blade + Livewire 3 + Alpine.js.
Todas as páginas estendem `layouts/app.blade.php`.
A sidebar é o elemento central de navegação — colapsável no mobile.

---

## Layout principal (app.blade.php)

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Painel') · Card</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="bg-[#F5F5F3] font-['Inter'] text-[#222]" x-data="{ sidebarOpen: false }">

  {{-- Topbar --}}
  <header class="h-12 bg-[#003049] flex items-center justify-between px-4 fixed top-0 left-0 right-0 z-30">
    <div class="flex items-center gap-2">
      {{-- Menu hamburguer mobile --}}
      <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-[#EAE2B7] mr-1">
        <svg data-lucide="menu" class="w-5 h-5"></svg>
      </button>
      <svg data-lucide="credit-card" class="w-[18px] h-[18px] text-[#FCBF49]"></svg>
      <span class="text-[#EAE2B7] text-[15px] font-semibold">Card</span>
    </div>
    <div class="flex items-center gap-2.5">
      @if(auth()->user()->plan === 'pro')
        <span class="bg-[#F77F00] text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">PRO</span>
      @elseif(auth()->user()->trial_ends_at?->isFuture())
        <span class="bg-[#F77F00] text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
          Trial · {{ auth()->user()->trial_ends_at->diffInDays() }}d
        </span>
      @else
        <span class="bg-[#888] text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">FREE</span>
      @endif
      <div class="w-[30px] h-[30px] rounded-full bg-[#FCBF49] flex items-center justify-center
                  text-[12px] font-semibold text-[#003049]">
        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
      </div>
    </div>
  </header>

  <div class="flex pt-12 min-h-screen">

    {{-- Sidebar overlay mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/40 z-20 md:hidden" x-transition.opacity></div>

    {{-- Sidebar --}}
    <aside class="fixed left-0 top-12 bottom-0 z-20 bg-white border-r border-[#E0E0DE]
                  w-[54px] flex flex-col items-center py-3 gap-1
                  transition-transform md:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

      @php $nav = request()->routeIs('dashboard.*') ? request()->route()->getName() : ''; @endphp

      <x-nav-item route="dashboard.index"     icon="layout-dashboard" :active="$nav === 'dashboard.index'" />
      <x-nav-item route="dashboard.card"      icon="credit-card"      :active="$nav === 'dashboard.card'" />
      <x-nav-item route="dashboard.links"     icon="link"             :active="$nav === 'dashboard.links'" />
      <x-nav-item route="dashboard.photos"    icon="image"            :active="$nav === 'dashboard.photos'" />
      <x-nav-item route="dashboard.contacts"  icon="phone"            :active="$nav === 'dashboard.contacts'" />

      <hr class="w-7 border-t border-[#E0E0DE] my-1.5">

      @if(auth()->user()->plan === 'pro')
        <x-nav-item route="dashboard.schedule"     icon="calendar"        :active="$nav === 'dashboard.schedule'" />
        <x-nav-item route="dashboard.appointments" icon="check-square"    :active="$nav === 'dashboard.appointments'" />
        <x-nav-item route="dashboard.messages"     icon="message-circle"  :active="$nav === 'dashboard.messages'" />
      @else
        <x-nav-item-pro icon="calendar" tooltip="Agenda — Pro" />
        <x-nav-item-pro icon="message-circle" tooltip="Mensagens — Pro" />
      @endif

      <hr class="w-7 border-t border-[#E0E0DE] my-1.5">

      <x-nav-item route="dashboard.plan"     icon="star"      :active="$nav === 'dashboard.plan'" />
      <x-nav-item route="settings.index"     icon="settings"  :active="$nav === 'settings.index'" />

      <div class="mt-auto">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit"
                  class="w-[38px] h-[38px] rounded-[10px] flex items-center justify-center text-[#888] hover:text-[#D62828]">
            <svg data-lucide="log-out" class="w-[18px] h-[18px]"></svg>
          </button>
        </form>
      </div>
    </aside>

    {{-- Conteúdo principal --}}
    <main class="flex-1 ml-0 md:ml-[54px] min-h-screen">
      @yield('content')
    </main>

  </div>

  @livewireScripts
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (window.lucide) lucide.createIcons();
    });
  </script>
</body>
</html>
```

### Blade component: `x-nav-item`

```blade
{{-- resources/views/components/nav-item.blade.php --}}
@props(['route', 'icon', 'active' => false])
<a href="{{ route($route) }}"
   class="w-[38px] h-[38px] rounded-[10px] flex items-center justify-center
          {{ $active ? 'bg-[rgba(0,48,73,0.1)]' : 'hover:bg-[#F5F5F3]' }}">
  <svg data-lucide="{{ $icon }}"
       class="w-[18px] h-[18px] {{ $active ? 'text-[#003049]' : 'text-[#888]' }}"></svg>
</a>
```

### Blade component: `x-nav-item-pro` (bloqueado)

```blade
{{-- resources/views/components/nav-item-pro.blade.php --}}
@props(['icon', 'tooltip'])
<div class="relative group">
  <div class="w-[38px] h-[38px] rounded-[10px] flex items-center justify-center opacity-40 cursor-not-allowed">
    <svg data-lucide="{{ $icon }}" class="w-[18px] h-[18px] text-[#888]"></svg>
  </div>
  <div class="absolute left-full top-1/2 -translate-y-1/2 ml-2
              bg-[#003049] text-white text-[10px] font-medium px-2 py-1 rounded-[6px]
              whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
    {{ $tooltip }}
  </div>
</div>
```

---

## Componente Livewire: Overview (Dashboard)

```php
// app/Livewire/Dashboard/Overview.php
class Overview extends Component
{
    public function render(): View
    {
        $card  = auth()->user()->card;
        $views = $card?->views()->count() ?? 0;
        $month = $card?->views()->whereMonth('viewed_at', now()->month)->count() ?? 0;

        return view('livewire.dashboard.overview', compact('card', 'views', 'month'));
    }
}
```

```blade
{{-- resources/views/livewire/dashboard/overview.blade.php --}}
<div>
  {{-- Header da página --}}
  <div class="flex items-center justify-between px-4 py-3.5 bg-white border-b border-[#E0E0DE]">
    <div class="flex items-center gap-2 text-[14px] font-semibold text-[#222]">
      <svg data-lucide="layout-dashboard" class="w-4 h-4 text-[#003049]"></svg>
      Visão geral
    </div>
    <div class="flex gap-1.5">
      <a href="{{ route('card.show', $card->slug) }}" target="_blank"
         class="flex items-center gap-1.5 border border-[#ccc] rounded-[8px] px-2.5 py-[5px]
                text-[12px] font-medium text-[#222]">
        <svg data-lucide="external-link" class="w-[14px] h-[14px]"></svg>
        Ver cartão
      </a>
      <button wire:click="$dispatch('open-share-modal')"
              class="flex items-center gap-1.5 bg-[#D62828] text-white rounded-[8px] px-2.5 py-[5px]
                     text-[12px] font-medium">
        <svg data-lucide="share-2" class="w-[14px] h-[14px]"></svg>
        Compartilhar
      </button>
    </div>
  </div>

  {{-- Body --}}
  <div class="p-4 flex flex-col gap-3">

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-2.5">
      <div class="bg-[#EBEBEA] rounded-[10px] p-3.5">
        <p class="text-[11px] text-[#888] mb-1">Total de visitas</p>
        <p class="text-[22px] font-medium text-[#222]">{{ number_format($views) }}</p>
        <p class="text-[11px] text-[#666] mt-0.5">desde o início</p>
      </div>
      <div class="bg-[#EBEBEA] rounded-[10px] p-3.5">
        <p class="text-[11px] text-[#888] mb-1">Este mês</p>
        <p class="text-[22px] font-medium text-[#222]">{{ number_format($month) }}</p>
        <p class="text-[11px] text-[#666] mt-0.5">{{ now()->format('F Y') }}</p>
      </div>
    </div>

    {{-- Card de compartilhamento --}}
    <div class="bg-[#EBEBEA] rounded-[10px] p-3.5 flex items-center gap-3">
      <div class="w-12 h-12 bg-[#D0D0CE] rounded-[6px] flex items-center justify-center flex-shrink-0">
        <svg data-lucide="qr-code" class="w-5 h-5 text-[#888]"></svg>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-[12px] font-medium text-[#003049] font-mono truncate">
          card.app/u/{{ $card->slug }}
        </p>
        <p class="text-[11px] text-[#888] mt-0.5">Seu link personalizado</p>
        <div class="flex gap-1.5 mt-1.5">
          <button onclick="navigator.clipboard.writeText('{{ url('/u/' . $card->slug) }}')"
                  class="flex items-center gap-1 border border-[#ccc] rounded-[6px] px-2 py-[3px]
                         text-[11px] text-[#222]">
            <svg data-lucide="copy" class="w-3 h-3"></svg>
            Copiar link
          </button>
          <a href="{{ route('dashboard.index') }}#qr"
             class="flex items-center gap-1 border border-[#ccc] rounded-[6px] px-2 py-[3px]
                    text-[11px] text-[#222] no-underline">
            <svg data-lucide="download" class="w-3 h-3"></svg>
            QR Code
          </a>
        </div>
      </div>
    </div>

    {{-- Atalhos de upload rápido --}}
    <div class="grid grid-cols-2 gap-2.5">
      <a href="{{ route('dashboard.card') }}"
         class="border border-dashed border-[#bbb] rounded-[10px] p-3
                flex flex-col items-center gap-1.5 bg-[#FAFAF9] no-underline">
        <svg data-lucide="image" class="w-5 h-5 text-[#aaa]"></svg>
        <span class="text-[11px] text-[#aaa] text-center">Foto de capa</span>
      </a>
      <a href="{{ route('dashboard.card') }}"
         class="border border-dashed border-[#bbb] rounded-[10px] p-3
                flex flex-col items-center gap-1.5 bg-[#FAFAF9] no-underline">
        <svg data-lucide="user-circle" class="w-5 h-5 text-[#aaa]"></svg>
        <span class="text-[11px] text-[#aaa] text-center">Foto de perfil</span>
      </a>
    </div>

  </div>
</div>
```

---

## Job assíncrono de visualizações

```php
// app/Jobs/RecordCardView.php
class RecordCardView implements ShouldQueue
{
    public function __construct(
        public readonly int $cardId,
        public readonly string $ipHash,
        public readonly string $userAgent
    ) {}

    public function handle(): void
    {
        // Evitar duplicatas por IP nas últimas 24h
        $exists = CardView::where('card_id', $this->cardId)
            ->where('ip_hash', $this->ipHash)
            ->where('viewed_at', '>=', now()->subHours(24))
            ->exists();

        if (!$exists) {
            CardView::create([
                'card_id'    => $this->cardId,
                'ip_hash'    => $this->ipHash,
                'user_agent' => $this->userAgent,
                'viewed_at'  => now(),
            ]);
        }
    }
}

// Disparar no CardController@show:
RecordCardView::dispatch(
    $card->id,
    hash('sha256', $request->ip()),
    $request->userAgent()
)->onQueue('default');
```

---

## Checklist de entrega (T-032 a T-041)

- [ ] Layout `app.blade.php` com topbar + sidebar colapsável (Alpine)
- [ ] Componente `x-nav-item` com estado active correto
- [ ] Itens Pro na sidebar com tooltip (sem rota, apenas visual)
- [ ] Badge de plano no topbar (PRO / Trial / FREE)
- [ ] Livewire `Overview` com contadores de views
- [ ] Card de compartilhamento com URL + botão copiar (JS Clipboard API)
- [ ] Job `RecordCardView` em fila, deduplicando por IP/24h
- [ ] Teste Feature: acessar dashboard → verificar contagens
