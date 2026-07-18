# CLAUDE.md — Card SaaS v1.1
> Entry point para Claude Code · PageUp Sistemas · Porto Velho, RO
> Leia este arquivo inteiro antes de qualquer ação no projeto.
> Atualizado: 2026-07 | Versão corrigida pelo arquiteto sênior.

---

## 1. O que é este projeto

**Card** é um SaaS multi-tenant de cartão de visita digital com agenda nativa.
Stack: Laravel 11 · Filament 3 · Livewire 3 · Blade · Alpine.js · Tailwind CSS · MySQL 8.
Idioma de toda interface e código: **Português BR**.

Documentos canônicos (leia antes de implementar qualquer módulo):
```
docs/constitution.md    → decisões arquiteturais imutáveis
docs/requisitos.md      → todos os requisitos funcionais
docs/prd.md             → histórias de usuário priorizadas
docs/arquitetura.md     → estrutura de pastas, padrões, migrations
docs/design-system.md   → ⭐ NOVO — sistema visual completo (LEIA SEMPRE)
docs/tasks.md           → breakdown atômico de implementação
```

---

## 2. Regras absolutas

### Código
- **Nunca altere** `constitution.md` sem aprovação explícita do usuário.
- **Nunca crie migrations** sem verificar se a tabela já existe (`php artisan migrate:status`).
- **Nunca use Redis** — fila usa driver `database` (servidor compartilhado).
- **Nunca use subdomínio wildcard** no MVP — URL é `/u/{slug}`.
- **Nunca processe pagamentos** diretamente — apenas Efi Bank SDK.
- **Nunca armazene cor sem validar** formato HEX (`/^#[0-9A-Fa-f]{6}$/`) no backend.
- **Sempre rode** `php artisan test` após implementar um módulo.
- **Sempre pergunte** antes de deletar arquivos ou dados.

### Design e UI (regras críticas)
- **Nunca use Tabler Icons** — os mockups HTML usam Tabler como protótipo apenas.
  Na implementação real: **somente Lucide Icons (outline)**.
- **Nunca use Heroicons ou FontAwesome** no painel do titular e no cartão público.
  (Heroicons são aceitáveis SOMENTE dentro do Filament Admin, pois é interno).
- **Nunca use outra fonte** além de **Inter** (Google Fonts).
- **Nunca escreva cores de marca como classes Tailwind arbitrárias** para elementos dinâmicos.
  Use `style="color: var(--card-primary)"` para respeitar as cores do titular.
- **Nunca use `<img>` para ícones** — use `<svg data-lucide="nome">` com `lucide.createIcons()`.

---

## 3. Design System — resumo essencial

> Arquivo completo: `docs/design-system.md`

### Paleta da marca Card
| Token | Hex | Uso |
|---|---|---|
| `--color-primary` | `#003049` | Prussian Blue — header, navbar |
| `--color-action` | `#D62828` | CTA primário padrão |
| `--color-highlight` | `#F77F00` | PIX, botão laranja |
| `--color-accent` | `#FCBF49` | Badge Pro, ícone destaque |
| `--color-surface` | `#EAE2B7` | Rodapé cartão, watermark |
| `--color-bg-page` | `#F0F0EE` | Fundo da página |
| `--color-bg-muted` | `#EBEBEA` | Cards de stat, inputs |

### Cores dinâmicas do cartão (usuário Pro)
```blade
{{-- Em layouts/card.blade.php --}}
<style>
  :root {
    --card-primary: {{ $card->primary_color }};
    --card-button:  {{ $card->button_color }};
  }
</style>
```

### Ícones Lucide — CDN (páginas sem Vite)
```html
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<i data-lucide="credit-card" class="w-4 h-4"></i>
<script>lucide.createIcons();</script>
```

### Fonte Inter
```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
```

---

## 4. Estrutura de pastas

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                           ← Breeze (não modificar controllers)
│   │   ├── CardController.php              ← cartão público + VCF + QR downloads
│   │   ├── AppointmentController.php       ← agendamento público + tokens confirm/refuse
│   │   ├── ContactController.php           ← formulário de contato
│   │   ├── EfiBankWebhookController.php    ← webhook Efi Bank
│   │   └── Dashboard/                      ← controllers do painel
│   ├── Middleware/
│   │   └── CheckPlan.php                   ← verifica plano Pro para features restritas
│   └── Requests/
│       ├── StoreCardRequest.php
│       ├── UpdateCardRequest.php
│       ├── StoreAppointmentRequest.php
│       └── StoreContactMessageRequest.php
├── Livewire/
│   ├── Card/
│   │   ├── CardEditor.php                  ← editor principal
│   │   ├── ColorPicker.php                 ← pickers Pro com preview ao vivo
│   │   ├── CardPreview.php                 ← preview em tempo real
│   │   ├── LinkManager.php                 ← CRUD + drag-and-drop (SortableJS)
│   │   ├── PhotoGallery.php                ← upload e galeria
│   │   ├── ContactEditor.php               ← contatos do cartão
│   │   ├── ContactForm.php                 ← formulário no cartão público
│   │   └── MessageList.php                 ← histórico de mensagens (Pro)
│   ├── Schedule/
│   │   ├── ScheduleConfig.php              ← configurar disponibilidade semanal
│   │   ├── AppointmentCalendar.php         ← calendário público
│   │   └── AppointmentList.php             ← lista de agendamentos
│   └── Dashboard/
│       └── Overview.php                    ← métricas do painel
├── Models/
│   ├── User.php
│   ├── Card.php                            ← accessors primary_color, button_color
│   ├── CardLink.php
│   ├── CardPhoto.php
│   ├── ContactMessage.php
│   ├── CardView.php
│   ├── CardSchedule.php
│   ├── CardScheduleSlot.php
│   ├── CardAppointment.php
│   └── AuditLog.php                        ← log de impersonação admin
├── Services/
│   ├── PlanService.php                     ← lógica de plano, limites, upgrade
│   ├── EfiBankService.php                  ← integração Efi Bank SDK
│   ├── QrCodeService.php                   ← QR Code cartão + PIX EMV
│   ├── VCardService.php                    ← geração .vcf 3.0
│   ├── ImageService.php                    ← upload, crop, thumbnail
│   ├── SocialLinkService.php               ← detecção de rede social por URL
│   └── AppointmentService.php              ← slots e agendamentos
├── Jobs/
│   ├── SendAppointmentNotification.php
│   ├── SendContactMessage.php
│   ├── GenerateThumbnail.php
│   └── RecordCardView.php                  ← view assíncrona (deduplicada 24h)
├── Mail/
│   ├── ContactMessageMail.php
│   ├── AppointmentRequestedMail.php
│   ├── AppointmentConfirmedMail.php
│   ├── AppointmentRefusedMail.php
│   ├── PlanExpiringMail.php
│   ├── PlanExpiredMail.php
│   └── PlanDowngradedMail.php
├── Observers/
│   └── UserObserver.php                    ← cria Card automaticamente ao verificar e-mail
├── Listeners/
│   └── ActivateTrialOnVerification.php     ← trial de 14 dias ao verificar e-mail
└── Filament/
    ├── Resources/
    │   ├── UserResource.php
    │   ├── CardResource.php
    │   └── PlanResource.php
    ├── Widgets/
    │   └── SaasStatsOverview.php
    └── Pages/
        └── Dashboard.php

resources/views/
├── layouts/
│   ├── app.blade.php           ← painel autenticado (sidebar + topbar)
│   ├── guest.blade.php         ← auth (centrado, card branco)
│   └── card.blade.php          ← cartão público (cores dinâmicas via CSS vars)
├── components/
│   ├── icon.blade.php          ← <x-icon name="..." class="..."> wrapper Lucide
│   ├── nav-item.blade.php      ← item de navegação sidebar
│   └── nav-item-pro.blade.php  ← item bloqueado com tooltip
├── card/
│   └── show.blade.php          ← template do cartão público
├── livewire/
│   ├── card/
│   ├── schedule/
│   └── dashboard/
└── emails/
    ├── contact-message.blade.php
    ├── appointment-requested.blade.php
    ├── appointment-confirmed.blade.php
    ├── appointment-refused.blade.php
    └── plan-*.blade.php
```

---

## 5. Skills disponíveis

Carregue a skill do módulo antes de implementar.
Todas as skills agora têm conteúdo completo (não são mais stubs).

| Módulo | Skill | Status |
|---|---|---|
| M-01 Auth | `.claude/skills/auth.md` | ✅ Completa |
| M-02 Cartão + Cores + Capa | `.claude/skills/card-editor.md` | ✅ Completa |
| M-03 Links e Redes Sociais | `.claude/skills/links.md` | ✅ Completa |
| M-04 Contatos + vCard | `.claude/skills/contacts.md` | ✅ Completa |
| M-05 Formulário de Contato | `.claude/skills/form.md` | ✅ Completa |
| M-06 QR Code + Compartilhamento | `.claude/skills/qrcode.md` | ✅ Completa |
| M-07 Painel do Usuário | `.claude/skills/dashboard.md` | ✅ Completa |
| M-08 Billing + Efi Bank | `.claude/skills/billing.md` | ✅ Completa |
| M-09 Admin Filament | `.claude/skills/admin.md` | ✅ Completa |
| M-10 Agenda + Agendamentos | `.claude/skills/agenda.md` | ✅ Completa |

---

## 6. MCP configurados

```json
{
  "mcpServers": {
    "filesystem": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-filesystem", "/caminho/do/projeto/card"]
    },
    "github": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": { "GITHUB_TOKEN": "SEU_TOKEN_AQUI" }
    },
    "playwright": {
      "command": "npx",
      "args": ["-y", "@playwright/mcp"]
    },
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"]
    }
  }
}
```

> Configure em `~/.claude/claude_desktop_config.json`.

---

## 7. Fluxo de trabalho padrão

Para cada módulo, siga esta sequência obrigatória:

```
1. LEIA     → CLAUDE.md + docs/constitution.md + docs/design-system.md + skill do módulo
2. PLANEJE  → liste as tarefas antes de escrever código
3. AGUARDE  → aprovação do plano pelo usuário
4. IMPLEMENTE → migrations → models → services → controllers → views
5. VERIFIQUE → design: Inter carregado? Lucide? Cores via var()? Mobile-first?
6. TESTE    → php artisan test --filter=NomeDoModulo
7. COMMIT   → feat(modulo): descrição curta em português
8. RELATE   → liste o que foi feito e o que está pendente
```

**Nunca pule a etapa 3 (aprovação).** Se o plano tiver mais de 5 arquivos
novos, divida em sub-fases.

---

## 8. Padrões de código

### Models
```php
// Sempre defina $fillable explicitamente
// Sempre defina casts para enum, bool e datetime
// Sempre defina relacionamentos completos
class Card extends Model {
    protected $fillable = [...];
    protected $casts = [
        'is_active'      => 'boolean',
        'show_watermark' => 'boolean',
    ];

    // Accessors de cor (respeitam o plano)
    public function getPrimaryColorAttribute(): string
    {
        return ($this->user->plan === 'pro' && $this->brand_color_primary)
            ? $this->brand_color_primary
            : '#003049';
    }
    public function getButtonColorAttribute(): string
    {
        return ($this->user->plan === 'pro' && $this->brand_color_button)
            ? $this->brand_color_button
            : '#D62828';
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function links(): HasMany { return $this->hasMany(CardLink::class); }
    // ...
}
```

### Controllers
```php
// Use Form Requests para validação
// Use Services para lógica de negócio
// Controllers são finos — só orquestram
public function store(StoreCardRequest $request, ImageService $image): RedirectResponse
{
    $card = Card::create($request->validated());
    return redirect()->route('dashboard.card')->with('sucesso', 'Cartão salvo.');
}
```

### Livewire
```php
// Use #[Validate] para validação inline
// Use dispatch() para eventos entre componentes
// Evite queries N+1 — use with() no mount()
// wire:model.live para preview em tempo real
// wire:confirm para ações destrutivas
```

### Blade (cartão público)
```blade
{{-- Cores dinâmicas — NUNCA usar classes Tailwind arbitrárias para brand colors --}}
<style>
  :root {
    --card-primary: {{ $card->primary_color }};
    --card-button:  {{ $card->button_color }};
  }
</style>

{{-- Uso --}}
<div style="background-color: var(--card-primary)">...</div>
<button style="background-color: var(--card-button)">...</button>
```

### Ícones (Lucide) — uso correto
```blade
{{-- Via Blade component (painel + cartão público com Vite) --}}
<x-icon name="calendar" class="w-4 h-4 text-[#003049]" />

{{-- Via CDN (páginas sem Vite ou e-mails) --}}
<svg data-lucide="calendar" class="w-4 h-4"></svg>
{{-- Lembrar de chamar lucide.createIcons() no final do body --}}
```

### Tailwind — boas práticas
```
✅ Use classes Tailwind para: espaçamento, tipografia, layout, bordas, sombras
✅ Use var() para: cores de marca e cores dinâmicas do titular
✅ Use classes Tailwind de cor para: cores fixas do sistema (#888, #ccc, #222)
❌ Nunca: text-[#003049] em elementos que usam cor do titular (use var())
❌ Nunca: bg-red-600 para o botão CTA (use var(--card-button) ou bg-[#D62828])
```

---

## 9. Checklist de entrega do MVP

### Fase 0 — Scaffold
- [ ] Projeto Laravel 11 criado
- [ ] Breeze Livewire instalado
- [ ] Filament 3 instalado e configurado
- [ ] Pacotes extras instalados (spatie/*, simple-qrcode, intervention/image)
- [ ] Lucide npm instalado + Blade component `x-icon` criado
- [ ] Inter (Google Fonts) configurada em todos os layouts
- [ ] CSS vars do design system em app.css
- [ ] `.env` configurado (DB, Resend, Efi Bank)

### Módulos
- [ ] M-01 Auth — cadastro com slug, trial automático, cartão auto-criado
- [ ] M-02 Cartão — header capa/avatar, editor, preview ao vivo, cores Pro
- [ ] M-03 Links — CRUD, SortableJS, detecção redes, limite Free
- [ ] M-04 Contatos — editor, vCard 3.0, WhatsApp, Maps
- [ ] M-05 Formulário — Livewire, honeypot, rate limit, e-mail Resend
- [ ] M-06 QR Code — PNG/SVG, PIX EMV, modal compartilhamento, Open Graph
- [ ] M-07 Painel — layout sidebar, dashboard, views assíncronas
- [ ] M-08 Billing — Efi Bank, webhook, plano view, aviso vencimento
- [ ] M-09 Admin — Filament, resources, métricas, impersonação + audit log
- [ ] M-10 Agenda — config disponibilidade, calendário público, confirmação manual

### Transversais
- [ ] Cartão público `/u/{slug}` — todos os blocos renderizando na ordem correta
- [ ] Mobile-first: testado em 375px em todos os módulos
- [ ] Watermark visível no Free, oculta no Pro
- [ ] Features Pro com overlay de bloqueio (nunca silencioso)
- [ ] LGPD — política de privacidade, exclusão de conta com storage
- [ ] Meta tags SEO e Open Graph em `/u/{slug}`
- [ ] Deploy em servidor compartilhado (sem Docker, sem wildcard)
- [ ] `php artisan optimize` + `npm run build` em produção

---

## 10. Ordem de execução recomendada

```
Fase 0 (scaffold) → Fase 1 (auth) → Fase 2 (cartão público) → Fase 3 (painel/editor)
→ Fase 4 (links) → Fase 5 (contatos) → Fase 6 (formulário)
→ Fase 7 (QR Code) → Fase 8 (billing) ← em paralelo com → Fase 9 (admin)
→ Fase 10 (agenda) → Fase 11 (qualidade e deploy)
```

Cada fase termina com ao menos um `php artisan test` antes de avançar.

---

*CLAUDE.md v1.1 · Card SaaS · PageUp Sistemas · 2026*
*Atualizado: correção de ícones (Lucide only), skills completas, design-system.md adicionado*
