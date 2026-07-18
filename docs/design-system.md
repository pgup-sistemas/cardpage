# Card — Design System v1.0
> Especificação de UI/UX completa · PageUp Sistemas · Porto Velho, RO
> Este documento é AUTORITATIVO sobre todas as decisões visuais do sistema.
> Qualquer implementação deve consultar este arquivo antes de escrever HTML/Blade/CSS.

---

## 0. Aviso sobre ícones

**Biblioteca oficial: Lucide Icons (outline only)**

Os mockups HTML de referência usam Tabler Icons (`ti-*`) como protótipo rápido.
Durante a implementação real, TODOS os ícones devem usar Lucide.
Não misture bibliotecas. Não use Heroicons. Não use FontAwesome.

```html
<!-- CDN (cartão público, e-mails, páginas sem build) -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>lucide.createIcons();</script>
<i data-lucide="instagram" class="w-4 h-4"></i>

<!-- NPM (assets compilados com Vite) -->
<!-- npm install lucide -->
<!-- Via Alpine.js + Blade component -->
```

---

## 1. Paleta de Cores

### 1.1 Tokens da marca Card (sistema)

```css
:root {
  /* Primárias */
  --color-primary:   #003049;   /* Prussian Blue  — header, navbar, texto forte   */
  --color-action:    #D62828;   /* Vermelho        — botão CTA primário padrão     */
  --color-highlight: #F77F00;   /* Laranja         — PIX, botão secundário          */
  --color-accent:    #FCBF49;   /* Amarelo         — badge Pro, ícones sobre escuro */
  --color-surface:   #EAE2B7;   /* Champagne       — rodapé cartão, marca d'água   */

  /* Neutros */
  --color-bg:        #FFFFFF;   /* Fundo cartão e painel                           */
  --color-bg-page:   #F0F0EE;   /* Fundo da página (fora do cartão/painel)         */
  --color-bg-subtle: #F5F5F3;   /* Fundo de seções alternadas no painel            */
  --color-bg-muted:  #EBEBEA;   /* Cards de stat, upload box, inputs               */
  --color-border:    #E0E0DE;   /* Divisores, bordas de input                      */
  --color-border-light: #E5E5E3; /* Divisores internos do cartão                  */

  /* Textos */
  --color-text:      #1A1F2E;   /* Texto principal                                 */
  --color-text-body: #52514E;   /* Texto de corpo / bio                            */
  --color-text-muted:#888888;   /* Labels, captions, placeholders                  */

  /* Semânticas */
  --color-success:   #16A34A;   /* Confirmado, sucesso                             */
  --color-warning:   #D97706;   /* Pendente, aviso                                 */
  --color-danger:    #DC2626;   /* Erro, recusado                                  */
  --color-info:      #2563EB;   /* Informação, link                                */

  /* Pro */
  --color-pro-bg:    #F5F0FF;   /* Fundo de feature Pro bloqueada                  */
  --color-pro-fg:    #7C3AED;   /* Texto/ícone de badge Pro                        */
}
```

### 1.2 Cores dinâmicas do cartão (por usuário)

```css
/* Aplicadas via <style> inline no layout card.blade.php */
:root {
  --card-primary: {{ $card->primary_color }};  /* Accessor → brand_color_primary ?? #003049 */
  --card-button:  {{ $card->button_color }};   /* Accessor → brand_color_button  ?? #D62828 */
}

/* Uso no Blade do cartão público */
.card-header    { background-color: var(--card-primary); }
.btn-cta        { background-color: var(--card-button); }
.btn-cta:hover  { filter: brightness(0.92); }
```

### 1.3 Mapeamento por contexto

| Contexto | Cor usada |
|---|---|
| Header do cartão (sem foto de capa) | `var(--card-primary)` |
| Ícones de redes sociais (sobre fundo escuro) | `var(--color-surface)` / `#EAE2B7` |
| Botão CTA principal (Salvar, Enviar) | `var(--card-button)` |
| Botão PIX | `var(--color-highlight)` / `#F77F00` |
| Botão outline / link | `var(--card-primary)` com borda |
| Badge Pro | bg `#FCBF49`, text `#003049` |
| Badge plano Trial | bg `#F77F00`, text `#fff` |
| Status pendente | bg `#FEF3C7`, text `#92400E` |
| Status confirmado | bg `#D1FAE5`, text `#065F46` |
| Status recusado | bg `#FEE2E2`, text `#991B1B` |
| Watermark (Free) | bg `#EAE2B7`, text `#003049` opacity 60% |

---

## 2. Tipografia

### 2.1 Família

```html
<!-- Google Fonts — única fonte do sistema -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
```

```css
body { font-family: 'Inter', sans-serif; }
```

**Nunca use outra família.** Nunca use `system-ui` como fallback principal.

### 2.2 Escala tipográfica

| Contexto | Tamanho | Peso | line-height |
|---|---|---|---|
| Nome no cartão (display_name) | 17px | 600 | 1.3 |
| Heading do painel (page title) | 14px | 600 | 1.4 |
| Subtítulo / cargo / empresa | 12px | 400 | 1.4 |
| Corpo / bio | 13px | 400 | 1.65 |
| Label de campo | 11px | 500 | 1.4 |
| Botão CTA | 13px | 500 | — |
| Badge / tag | 10px | 600 | — |
| Caption / rodapé | 11px | 400 | 1.4 |
| Código / URL monospace | 12px | 400 | — (`font-family: monospace`) |
| Stat value (dashboard) | 22px | 500 | 1.2 |

### 2.3 Tailwind CSS — classes mapeadas

```
text-[17px] font-semibold   → nome principal
text-sm font-semibold        → heading painel (text-sm = 14px)
text-xs text-gray-500        → subtítulo/cargo
text-[13px]                  → corpo/bio
text-[11px] font-medium      → labels de campo
text-[10px] font-semibold    → badges
font-mono text-xs            → URL / código
text-[22px] font-medium      → stat value
```

> **Nota:** evite usar classes Tailwind arbitrárias para cores de marca.
> Use `style="color: var(--card-primary)"` para cores dinâmicas.

---

## 3. Ícones Lucide

### 3.1 Instalação e uso

```bash
npm install lucide
```

```php
{{-- Blade component (criar em resources/views/components/icon.blade.php) --}}
@props(['name', 'class' => 'w-4 h-4'])
<svg data-lucide="{{ $name }}" {{ $attributes->merge(['class' => $class]) }}></svg>
```

```blade
{{-- Uso --}}
<x-icon name="phone" class="w-4 h-4 text-[#003049]" />
<x-icon name="calendar" class="w-5 h-5" />
```

### 3.2 Tamanhos canônicos

| Contexto | Tailwind | px |
|---|---|---|
| Ícone inline em texto | `w-3.5 h-3.5` | 14px |
| Ícone em botões e campos | `w-4 h-4` | 16px |
| Ícone decorativo em cards | `w-5 h-5` | 20px |
| Ícone hero / empty state | `w-6 h-6` | 24px |
| Ícone nav sidebar | `w-[18px] h-[18px]` | 18px |

### 3.3 Mapa de ícones por função

#### Navegação do painel

| Seção | Ícone Lucide |
|---|---|
| Dashboard / visão geral | `layout-dashboard` |
| Editor do cartão | `credit-card` |
| Links e redes | `link` |
| Galeria de fotos | `image` |
| Contatos | `phone` |
| Agenda | `calendar` |
| Mensagens | `message-circle` |
| Plano / Upgrade | `star` |
| Configurações | `settings` |
| Sair | `log-out` |

#### Ações gerais

| Ação | Ícone Lucide |
|---|---|
| Salvar | `save` |
| Editar | `pencil` |
| Excluir | `trash-2` |
| Adicionar | `plus` |
| Arrastar (drag) | `grip-vertical` |
| Ativar/desativar (toggle) | `toggle-left` / `toggle-right` |
| Upload | `upload` |
| Download | `download` |
| Copiar | `copy` |
| Compartilhar | `share-2` |
| Ver | `eye` |
| Fechar / Recusar | `x` / `x-circle` |
| Confirmar | `check` / `check-circle` |
| Abrir link | `external-link` |
| Seta direita (em botão) | `arrow-right` |
| Filtro | `sliders-horizontal` |
| Busca | `search` |
| Aviso | `alert-triangle` |
| Info | `info` |

#### Cartão público

| Elemento | Ícone Lucide |
|---|---|
| Salvar contato (vCard) | `user-plus` |
| Telefone / ligação | `phone` |
| WhatsApp | `message-circle` |
| E-mail | `mail` |
| Endereço / localização | `map-pin` |
| Site / portfólio | `globe` |
| PIX | `qr-code` |
| Agendar horário | `calendar` |
| Horário | `clock` |
| Formulário de contato | `send` |
| Galeria | `image` |
| Portfólio | `briefcase` |

#### Redes sociais (detecção automática por URL)

| Rede | Ícone Lucide | URL trigger |
|---|---|---|
| Instagram | `instagram` | instagram.com |
| LinkedIn | `linkedin` | linkedin.com |
| YouTube | `youtube` | youtube.com |
| TikTok | `music` | tiktok.com |
| X / Twitter | `twitter` | twitter.com, x.com |
| Facebook | `facebook` | facebook.com |
| Telegram | `send` | telegram.me, t.me |
| Pinterest | `image` | pinterest.com |
| Spotify | `music-2` | spotify.com |
| WhatsApp | `message-circle` | wa.me, whatsapp.com |
| GitHub | `github` | github.com |
| Link genérico | `link` | (padrão) |

#### Status de agendamento

| Status | Ícone | Cor |
|---|---|---|
| Pendente | `clock` | `#D97706` |
| Confirmado | `check-circle` | `#16A34A` |
| Recusado | `x-circle` | `#DC2626` |
| Cancelado | `ban` | `#6B7280` |

---

## 4. Sistema de Layout

### 4.1 Breakpoints (mobile-first)

```css
/* Tailwind defaults usados no projeto */
/* sm:  640px  — tablet pequeno         */
/* md:  768px  — tablet padrão          */
/* lg: 1024px  — desktop                */
/* xl: 1280px  — desktop grande         */

/* Regra: design começa do menor → maior */
/* Cartão público: nunca usa breakpoints (sempre mobile) */
```

### 4.2 Cartão público `/u/{slug}`

```
max-width: 400px
margin: auto
padding: 2rem 1rem (fora do frame do cartão)
background-page: #F0F0EE

Estrutura do cartão:
┌─ .phone-frame ──────────────────────┐
│  border-radius: 18px                │
│  overflow: hidden                   │
│  box-shadow: 0 4px 32px rgba(0,0,0,.12) │
│                                     │
│  ┌─ .cover ────────────────────────┐│
│  │  height: 110px                  ││
│  │  background: var(--card-primary)││
│  │  [foto de capa como bg-image]   ││
│  │                         [tag]   ││
│  └─────────────────────────────────┘│
│  [avatar 58px, sobreposição -28px]  │
│                                     │
│  ┌─ .card-info ────────────────────┐│
│  │  padding: 36px 18px 14px        ││
│  │  (espaço para o avatar)         ││
│  └─────────────────────────────────┘│
│                                     │
│  [blocos com divider .5px]          │
│                                     │
│  ┌─ .card-footer ─────────────────┐ │
│  │  bg: #EAE2B7 (watermark Free)  │ │
│  └────────────────────────────────┘ │
└─────────────────────────────────────┘
```

#### Estrutura do header do cartão

```
┌─────────────────────────────────────┐
│ [cover_photo 100% × 110px]          │  → background-image ou background-color
│ overlay: rgba(0,0,0,0.15)           │
│                         [badge tag] │  → "foto de capa" / "PRO"
└─────────────────────────────────────┘
  [avatar 58px]                          → position: absolute, bottom: -28px, left: 18px
  border: 3px solid #fff                  borda branca, border-radius 50%
  background: #FCBF49 (fallback)          iniciais do titular

 padding-top: 36px  (espaço pós-avatar)
 display_name  → 17px / 600 / #003049
 title + company → 12px / 400 / #6B6B6B
 badge Pro   → 10px / 600 / bg #FCBF49 text #003049 / border-radius 20px
 [ícones redes sociais] → w-8 h-8, bg var(--card-primary), icon color #EAE2B7
```

### 4.3 Painel do usuário `/dashboard/*`

```
┌─ topbar (height: 48px, bg: #003049) ──────────────────┐
│  [logo credit-card #FCBF49]  Card    [badge plano] [avatar] │
└───────────────────────────────────────────────────────┘

┌─ sidebar (54px colapsada) ┬─ main content ──────────────┐
│  [nav-item × 10]           │  .panel-header (48px)       │
│  w-[38px] h-[38px]         │    título + ações           │
│  border-radius: 10px        │                             │
│  active: bg rgba(0,48,73,.1)│  .panel-body               │
│                             │    padding: 14px 16px       │
│  [dividers entre grupos]    │    gap: 12px                │
└────────────────────────────┴─────────────────────────────┘

Mobile: sidebar se torna bottom nav ou drawer (Alpine.js)
Sidebar expanded (md+): 200px com labels de texto
```

#### Topbar do painel

```html
<header class="h-12 bg-[#003049] flex items-center justify-between px-4">
  <!-- Logo -->
  <div class="flex items-center gap-2">
    <x-icon name="credit-card" class="w-[18px] h-[18px] text-[#FCBF49]" />
    <span class="text-[#EAE2B7] text-[15px] font-semibold">Card</span>
  </div>
  <!-- Right -->
  <div class="flex items-center gap-2.5">
    <span class="bg-[#F77F00] text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">PRO</span>
    <div class="w-[30px] h-[30px] rounded-full bg-[#FCBF49] flex items-center justify-center
                text-[12px] font-semibold text-[#003049]">MJ</div>
  </div>
</header>
```

### 4.4 Telas de autenticação

```
Layout: guest.blade.php
Fundo: #F0F0EE
Card central: max-width 400px, bg #fff, border-radius 14px,
              box-shadow 0 4px 24px rgba(0,0,0,.10), padding 32px

Logo: credit-card icon #FCBF49 + "Card" text #003049
Heading: 20px / 600 / #003049
Subtítulo: 13px / 400 / #888
```

### 4.5 Espaçamento padrão

| Elemento | Tailwind | px |
|---|---|---|
| Padding de bloco no cartão público | `p-[18px]` | 18px |
| Gap entre blocos / itens | `gap-[8px]` a `gap-[12px]` | 8–12px |
| Padding do painel (body) | `p-[16px]` | 16px |
| Margem de seção | `mb-[12px]` a `mb-[14px]` | 12–14px |
| Border radius de cards | `rounded-[10px]` | 10px |
| Border radius de botões | `rounded-[8px]` a `rounded-[10px]` | 8–10px |
| Border radius de avatares | `rounded-full` | 50% |
| Border radius de badges | `rounded-full` | 50% |

---

## 5. Componentes de UI

### 5.1 Botões

#### CTA Primário (ação principal do titular)
```html
<button class="flex items-center justify-center gap-[7px] w-full
               bg-[var(--card-button,#D62828)] text-white
               text-[13px] font-medium rounded-[10px] px-4 py-[10px]
               hover:brightness-90 transition-all">
  <x-icon name="send" class="w-4 h-4" />
  Enviar mensagem
</button>
```

#### CTA Secundário (PIX, ação de destaque laranja)
```html
<button class="flex items-center justify-center gap-[7px] w-full
               bg-[#F77F00] text-white
               text-[13px] font-medium rounded-[10px] px-4 py-[10px]">
  <x-icon name="qr-code" class="w-4 h-4" />
  Pagar via PIX
</button>
```

#### Outline (link ou ação secundária)
```html
<button class="flex items-center justify-center gap-[7px] w-full
               bg-transparent border border-[var(--card-primary,#003049)]
               text-[var(--card-primary,#003049)]
               text-[13px] font-medium rounded-[10px] px-4 py-[10px]">
  <x-icon name="calendar" class="w-4 h-4" />
  Agendar horário
</button>
```

#### Botão pequeno (ações do painel)
```html
<button class="flex items-center gap-[5px]
               border border-[#ccc] rounded-[8px] px-[10px] py-[5px]
               text-[12px] font-medium text-[#222] bg-transparent">
  <x-icon name="share-2" class="w-[14px] h-[14px]" />
  Compartilhar
</button>
```

#### Botão pequeno primário (painel)
```html
<button class="flex items-center gap-[5px]
               bg-[#D62828] border-[#D62828] border rounded-[8px] px-[10px] py-[5px]
               text-[12px] font-medium text-white">
  <x-icon name="external-link" class="w-[14px] h-[14px]" />
  Ver cartão
</button>
```

### 5.2 Campos de formulário

```html
<!-- Label padrão -->
<label class="text-[11px] font-medium text-[#666]">Nome completo</label>

<!-- Input padrão -->
<input type="text"
       class="w-full border border-[#ccc] rounded-[8px] px-[10px] py-[8px]
              text-[13px] text-[#222] bg-[#FAFAF9] font-['Inter']
              focus:outline-none focus:border-[#003049] focus:ring-1 focus:ring-[#003049]
              placeholder:text-[#aaa]">

<!-- Textarea -->
<textarea rows="4"
          class="w-full border border-[#ccc] rounded-[8px] px-[10px] py-[8px]
                 text-[13px] text-[#222] bg-[#FAFAF9] font-['Inter']
                 resize-none focus:outline-none focus:border-[#003049]"></textarea>

<!-- Select -->
<select class="w-full border border-[#ccc] rounded-[8px] px-[10px] py-[8px]
               text-[13px] text-[#222] bg-[#FAFAF9]">
  <option>30 minutos</option>
</select>

<!-- Color picker com preview -->
<div class="flex items-center gap-2 border border-[#ccc] rounded-[8px] px-[10px] py-[7px] bg-[#FAFAF9]">
  <div class="w-5 h-5 rounded-[5px] flex-shrink-0" style="background: var(--card-primary)"></div>
  <input type="color" wire:model.live="brand_color_primary" class="sr-only">
  <span class="text-[12px] font-mono text-[#222] flex-1">{{ $card->brand_color_primary ?? '#003049' }}</span>
  <x-icon name="pipette" class="w-[14px] h-[14px] text-[#888]" />
</div>

<!-- Grupo campo + label -->
<div class="flex flex-col gap-1">
  <label class="text-[11px] font-medium text-[#666]">Cor primária</label>
  <!-- ... input ... -->
</div>
```

### 5.3 Badges e tags

```html
<!-- Badge Pro -->
<span class="inline-flex items-center gap-1
             bg-[#FCBF49] text-[#003049]
             text-[10px] font-semibold px-2 py-0.5 rounded-full">
  <x-icon name="star" class="w-3 h-3" />
  PRO
</span>

<!-- Badge Trial -->
<span class="inline-flex items-center gap-1
             bg-[#F77F00] text-white
             text-[10px] font-semibold px-2 py-0.5 rounded-full">
  Trial — 12 dias restantes
</span>

<!-- Badge status agendamento -->
<span class="inline-flex items-center gap-1
             bg-[#FEF3C7] text-[#92400E]
             text-[10px] font-semibold px-2 py-0.5 rounded-[6px]">
  <x-icon name="clock" class="w-3 h-3" />
  Pendente
</span>

<!-- Feature Pro bloqueada -->
<div class="inline-flex items-center gap-[5px]
            bg-[#F5F0FF] rounded-[8px] px-[10px] py-[5px]">
  <x-icon name="lock" class="w-[13px] h-[13px] text-[#7C3AED]" />
  <span class="text-[11px] text-[#7C3AED]">Disponível no plano Pro</span>
</div>
```

### 5.4 Cards de stat (dashboard)

```html
<div class="bg-[#EBEBEA] rounded-[10px] p-3.5">
  <p class="text-[11px] text-[#888] mb-1">Visualizações</p>
  <p class="text-[22px] font-medium text-[#222]">1.247</p>
  <p class="text-[11px] text-[#666] mt-0.5">últimos 30 dias</p>
</div>
```

### 5.5 Ícones de rede social (no cartão público)

```html
<div class="flex gap-2 mt-2.5">
  @foreach($card->activeLinks()->social()->get() as $link)
  <a href="{{ $link->url }}" target="_blank" rel="noopener"
     class="w-8 h-8 rounded-[8px] flex items-center justify-center"
     style="background-color: var(--card-primary)">
    <x-icon name="{{ $link->icon }}" class="w-4 h-4 text-[#EAE2B7]" />
  </a>
  @endforeach
</div>
```

### 5.6 Itens de link no cartão (botões de ação)

```html
<!-- Botão link padrão (CTA) -->
<a href="{{ $link->url }}" target="_blank"
   class="flex items-center gap-2.5 w-full px-3.5 py-[11px]
          rounded-[10px] text-[13px] font-medium
          bg-[var(--card-button,#D62828)] text-white no-underline">
  <x-icon name="{{ $link->icon }}" class="w-4 h-4 flex-shrink-0" />
  <span class="flex-1 text-left">{{ $link->label }}</span>
  <x-icon name="arrow-right" class="w-[14px] h-[14px] opacity-70" />
</a>

<!-- Botão outline -->
<a href="{{ $link->url }}" target="_blank"
   class="flex items-center gap-2.5 w-full px-3.5 py-[11px]
          rounded-[10px] text-[13px] font-medium bg-transparent no-underline
          border-[1.5px]"
   style="border-color: var(--card-primary); color: var(--card-primary)">
  ...
</a>
```

### 5.7 Dividers

```html
<!-- Separador entre blocos do cartão -->
<hr class="border-0 border-t border-[#E5E5E3] mx-[18px]">

<!-- Separador no painel/sidebar -->
<hr class="border-0 border-t border-[#E0E0DE] w-7 my-1.5">
```

### 5.8 Toast de confirmação (Alpine.js)

```html
<!-- No cartão público — after form submit -->
<div x-data="{ show: false }"
     x-on:form-sent.window="show = true; setTimeout(() => show = false, 4000)"
     x-show="show"
     x-transition
     class="fixed bottom-4 left-1/2 -translate-x-1/2
            bg-[#003049] text-white text-[13px] font-medium
            flex items-center gap-2 px-4 py-2.5 rounded-[10px]
            shadow-lg z-50">
  <x-icon name="check-circle" class="w-4 h-4 text-[#FCBF49]" />
  Mensagem enviada com sucesso!
</div>
```

### 5.9 Upload box

```html
<!-- Estado vazio -->
<div class="border border-dashed border-[#bbb] rounded-[10px] p-3
            flex flex-col items-center gap-1.5 cursor-pointer bg-[#FAFAF9]">
  <x-icon name="upload" class="w-5 h-5 text-[#aaa]" />
  <span class="text-[11px] text-[#aaa] text-center">Foto de capa<br>JPG, PNG · máx 5MB</span>
</div>

<!-- Estado preenchido -->
<div class="border border-[#003049] rounded-[10px] p-3
            flex flex-col items-center gap-1.5 cursor-pointer bg-[#003049]">
  <x-icon name="image" class="w-5 h-5 text-[#EAE2B7]" />
  <span class="text-[11px] text-[#EAE2B7] opacity-70 text-center">capa.jpg<br>clique para alterar</span>
</div>
```

### 5.10 Item de link no gerenciador (painel)

```html
<div class="flex items-center gap-2.5 p-2.5 bg-[#FAFAF9]
            border border-[#E0E0DE] rounded-[10px]">
  <!-- Drag handle -->
  <button class="cursor-grab text-[#aaa] flex-shrink-0">
    <x-icon name="grip-vertical" class="w-4 h-4" />
  </button>
  <!-- Ícone detectado -->
  <div class="w-8 h-8 rounded-[8px] bg-[#003049] flex items-center justify-center flex-shrink-0">
    <x-icon name="{{ $link->icon }}" class="w-4 h-4 text-[#EAE2B7]" />
  </div>
  <!-- Info -->
  <div class="flex-1 min-w-0">
    <p class="text-[13px] font-medium text-[#222] truncate">{{ $link->label }}</p>
    <p class="text-[11px] text-[#888] truncate">{{ $link->url }}</p>
  </div>
  <!-- Toggle + ações -->
  <div class="flex items-center gap-1.5 flex-shrink-0">
    <!-- Livewire toggle -->
    <button wire:click="toggleLink({{ $link->id }})" class="text-[#888]">
      <x-icon name="{{ $link->is_active ? 'toggle-right' : 'toggle-left' }}"
              class="w-5 h-5 {{ $link->is_active ? 'text-[#003049]' : 'text-[#ccc]' }}" />
    </button>
    <button wire:click="deleteLink({{ $link->id }})" class="text-[#aaa] hover:text-[#D62828]">
      <x-icon name="trash-2" class="w-4 h-4" />
    </button>
  </div>
</div>
```

---

## 6. Telas e Templates

### 6.1 Cartão público — ordem dos blocos

```
1. [header com capa e avatar]
2. .card-info: nome, cargo, badge, redes sociais
3. <divider>
4. [bio — se preenchida]
5. <divider>
6. [links customizados e redes — botões de ação]
7. <divider>
8. [contatos: telefone, e-mail, endereço, site]
9. <divider>
10. [galeria de fotos — se houver fotos]
11. <divider> (se galeria)
12. [bloco de agendamento — se agenda ativa e Pro]
13. <divider>
14. [formulário de contato]
15. .card-footer: watermark (Free) ou vazio (Pro)
```

### 6.2 Painel — estrutura de páginas

#### Dashboard (visão geral)
```
[topbar]
[sidebar | main]
  main:
    header: "Visão geral" + btn Compartilhar + btn Ver cartão
    body:
      [grid 2col] stats: visualizações + cliques
      [card compartilhar]: QR preview + URL + btns copiar/download
      [grid 2col] upload: capa + perfil (atalhos rápidos)
      [campos]: nome, cargo
```

#### Editor do cartão
```
[topbar]
[sidebar | main]
  main:
    header: "Meu cartão" + btn Salvar + btn Ver cartão
    body:
      [upload capa + upload perfil]
      [nome] [cargo]
      [empresa] [bio textarea]
      [cores de marca — Pro only, com lock icon no Free]
      [preview ao vivo — componente CardPreview]
```

#### Gerenciar links
```
[topbar]
[sidebar | main]
  main:
    header: "Links e redes" + btn Adicionar link
    body:
      [lista sortable de links — drag-and-drop]
      [modal/drawer: adicionar link]
        → detecta rede social pela URL
        → label + ícone preview + toggle ativo
      [info: limite Free (5/5)]
```

#### Agenda (Pro)
```
[topbar]
[sidebar | main]
  main:
    header: "Agenda" + toggle ativar/desativar
    body:
      [card configuração]:
        nome do serviço
        duração (select)
        [grid dias da semana com checkboxes]
        [por dia ativo: start_time - end_time]
      [lista agendamentos pendentes]
        → cada item: nome visitante, data/hora, status badge, btns confirm/recusar
```

#### Agendamentos recebidos
```
[topbar]
[sidebar | main]
  main:
    header: "Agendamentos" + filtro status
    body:
      [tabs: Pendentes | Confirmados | Recusados]
      [lista]:
        [item]: avatar iniciais | nome, e-mail | data hora | status | btns ação
```

### 6.3 Auth — layout e componentes

```
Fundo: #F0F0EE
Centralizado verticalmente, padding 2rem

Card:
  max-width: 400px
  bg: #fff
  border-radius: 14px
  box-shadow: 0 4px 24px rgba(0,0,0,.10)
  padding: 2rem

Logo:
  [icon credit-card #FCBF49 w-7 h-7]  [text "Card" 20px/600 #003049]
  texto centralizado, margin-bottom: 1.5rem

Campos:
  label 11px/500 #666
  input padrão (seção 5.2)
  gap entre campos: 12px

CTA:
  botão primário full-width (seção 5.1)
  margin-top: 20px

Links auxiliares:
  text-[13px] text-[#003049] underline
  "Já tem conta? Entrar"
```

### 6.4 Admin Filament — personalização

```php
// app/Providers/Filament/AdminPanelProvider.php
$panel
  ->colors(['primary' => Color::hex('#003049')])
  ->brandName('Card · Admin')
  ->brandLogo(asset('images/logo.svg'))
  ->favicon(asset('images/favicon.ico'))
  ->navigationGroups([
      NavigationGroup::make('Usuários e Cartões'),
      NavigationGroup::make('Billing'),
      NavigationGroup::make('Métricas'),
  ]);
```

#### Cores do Filament mapeadas para o design system
- Primary: `#003049` (Prussian Blue)
- Danger: `#DC2626`
- Warning: `#D97706`
- Success: `#16A34A`

---

## 7. E-mails transacionais

### 7.1 Template base

```
Fundo do e-mail:   #F0F0EE
Container central: max-width 600px, bg #fff, border-radius 8px
Header do e-mail:  bg #003049, padding 24px
  → [icon credit-card #FCBF49]  Card
Body:              padding 24px, font Inter 14px, color #1A1F2E, line-height 1.6
Footer:            bg #EAE2B7, padding 16px, font 11px, color #003049 opacity 60%
  → "Você recebeu este e-mail pois tem uma conta no Card. | Cancelar inscrição"
```

### 7.2 Botão de ação no e-mail

```html
<!-- Confirmar agendamento -->
<a href="{{ $confirmUrl }}"
   style="display:inline-block; background:#16A34A; color:#fff;
          text-decoration:none; padding:12px 28px; border-radius:8px;
          font-family:Inter,sans-serif; font-size:14px; font-weight:500; margin:8px 4px;">
  Confirmar agendamento
</a>

<!-- Recusar -->
<a href="{{ $refuseUrl }}"
   style="display:inline-block; background:#DC2626; color:#fff; ...">
  Recusar
</a>
```

### 7.3 Lista de e-mails do sistema

| Template | Gatilho | Para |
|---|---|---|
| `verify-email` | Cadastro | Titular |
| `reset-password` | Forgot password | Titular |
| `contact-message` | Formulário de contato no cartão | Titular |
| `appointment-requested` | Nova solicitação de agendamento | Titular |
| `appointment-confirmed` | Titular confirma | Visitante |
| `appointment-refused` | Titular recusa | Visitante |
| `plan-expiring-3d` | 3 dias antes do vencimento | Titular |
| `plan-expired` | No dia do vencimento | Titular |
| `plan-overdue-3d` | 3 dias após vencimento | Titular |
| `plan-upgraded` | Pagamento confirmado | Titular |
| `plan-downgraded` | Após inadimplência | Titular |

---

## 8. Estados de UI

### 8.1 Empty states

```html
<!-- Sem links cadastrados -->
<div class="flex flex-col items-center gap-3 py-10 text-center">
  <div class="w-12 h-12 rounded-full bg-[#EBEBEA] flex items-center justify-center">
    <x-icon name="link" class="w-6 h-6 text-[#aaa]" />
  </div>
  <p class="text-[13px] font-medium text-[#666]">Nenhum link ainda</p>
  <p class="text-[12px] text-[#aaa]">Adicione links de redes sociais ou personalizados</p>
  <button class="...">Adicionar link</button>
</div>
```

### 8.2 Loading state (Livewire)

```html
<div wire:loading class="flex items-center justify-center gap-2 text-[13px] text-[#888] py-4">
  <div class="w-4 h-4 border-2 border-[#003049] border-t-transparent
              rounded-full animate-spin"></div>
  Carregando...
</div>
```

### 8.3 Cartão inativo

```html
<!-- Quando $card->is_active === false -->
<div class="min-h-screen flex flex-col items-center justify-center
            bg-[#F0F0EE] text-center px-4">
  <x-icon name="credit-card" class="w-10 h-10 text-[#ccc] mb-4" />
  <h1 class="text-[17px] font-semibold text-[#003049]">Cartão indisponível</h1>
  <p class="text-[13px] text-[#888] mt-2">
    Este cartão está temporariamente desativado pelo titular.
  </p>
</div>
```

### 8.4 Feature Pro bloqueada (no painel)

```html
<div class="relative">
  <!-- Conteúdo bloqueado em blur -->
  <div class="pointer-events-none opacity-40 blur-[2px]">
    <!-- Conteúdo da feature Pro -->
  </div>
  <!-- Overlay -->
  <div class="absolute inset-0 flex flex-col items-center justify-center gap-3">
    <div class="bg-[#F5F0FF] rounded-[10px] p-4 text-center max-w-[220px]">
      <x-icon name="lock" class="w-6 h-6 text-[#7C3AED] mx-auto mb-2" />
      <p class="text-[13px] font-semibold text-[#7C3AED] mb-1">Recurso Pro</p>
      <p class="text-[11px] text-[#888] mb-3">Personalize as cores do seu cartão</p>
      <a href="{{ route('dashboard.plan') }}"
         class="text-[12px] font-medium text-white bg-[#7C3AED]
                px-3 py-1.5 rounded-[8px] no-underline">
        Fazer upgrade
      </a>
    </div>
  </div>
</div>
```

---

## 9. Animações e Transições

```css
/* Transição padrão de botões */
.btn { transition: filter 150ms ease, transform 80ms ease; }
.btn:hover { filter: brightness(0.92); }
.btn:active { transform: scale(0.98); }

/* Fade de toast */
[x-show] { transition: opacity 200ms ease; }

/* Slide do drawer mobile */
.drawer {
  transition: transform 250ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover em itens de lista */
.link-item { transition: background-color 120ms ease; }
.link-item:hover { background-color: #F0F0EE; }
```

---

## 10. Acessibilidade mínima

- Todos os botões têm texto visível ou `aria-label`
- Campos de formulário têm `<label>` associado via `for/id`
- Imagens têm `alt` descritivo ou `alt=""` se decorativas
- Contraste mínimo 4.5:1 entre texto e fundo
- Focus ring visível em inputs (`focus:ring-1 focus:ring-[#003049]`)
- Ícones Lucide têm `aria-hidden="true"` quando decorativos

```html
<!-- Botão só com ícone → obrigatório aria-label -->
<button aria-label="Compartilhar cartão">
  <x-icon name="share-2" class="w-4 h-4" aria-hidden="true" />
</button>
```

---

## 11. Checklist de conformidade do Design System

Antes de entregar qualquer view, verificar:

- [ ] Fonte: Inter carregada (Google Fonts no head)
- [ ] Ícones: Lucide only, sem Tabler/Heroicons
- [ ] Cores: tokens CSS usados, sem hex hardcoded fora de var()
- [ ] Cartão público: max-width 400px, centralizado
- [ ] Header: capa 110px, avatar -28px, borda 3px white
- [ ] Botões: rounded-[8px] a rounded-[10px], sem border-radius arbitrário
- [ ] Watermark: visível no Free, oculto no Pro
- [ ] Feature Pro: bloqueada com overlay, nunca silenciosa
- [ ] Empty states: todos os estados vazios tratados
- [ ] Mobile first: testado em 375px antes de 768px+
- [ ] Tailwind: classes arbitrárias de COR não usadas para cores de marca

---

*Design System v1.0 · Card SaaS · PageUp Sistemas · 2026*
*Atualizar este documento ao introduzir qualquer novo padrão visual.*
