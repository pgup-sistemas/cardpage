# Card — Constitution v1.2
> Cartão Digital SaaS · PageUp Sistemas · Porto Velho, RO
> Data: 2026-07-09 · Atualizado: 2026-07-20 · Status: DOCUMENTO CANÔNICO

---

## 1. O que é o Card

**Card** é um SaaS multi-tenant de cartão de visita digital. Qualquer pessoa cria um cartão público personalizado acessível via link (`dominio.com/u/slug`) e QR Code. O titular gerencia tudo via painel privado. A receita do SaaS vem de assinatura (planos Free e Pro). PIX é uma funcionalidade do cartão do usuário, não modelo de receita da plataforma.

---

## 2. Decisões arquiteturais — IMUTÁVEIS

Estas decisões não devem ser revertidas sem revisão formal do documento.

### 2.1 Multi-tenancy
- **Tenant = Usuário** (pessoa física ou profissional, nunca empresa)
- **Single-schema** com `user_id` em todas as tabelas de dados do cartão
- Um usuário = um cartão no MVP (múltiplos cartões é feature v1.1 para Pro)

### 2.2 URL pública
- Formato: `dominio.com/u/{slug}` — compatível com servidor compartilhado
- Slug único globalmente, escolhido pelo usuário no cadastro
- Slug imutável após 30 dias (evitar quebra de links/QR Codes impressos)
- Subdomínio wildcard (`slug.dominio.com`) apenas na v1.2 como feature Pro

### 2.3 Planos
- **2 planos no lançamento:** Free e Pro
- Free tem marca d'água obrigatória ("Criado com Card")
- Downgrade por inadimplência: automático para Free, **sem apagar dados**
- Trial de 14 dias do Pro, sem cartão de crédito obrigatório

### 2.4 Pagamentos
- **Efi Bank** (ex-Gerencianet) para billing do SaaS (integração existente)
- PIX, boleto e cartão de crédito — mensal e anual
- Webhooks para ativar/suspender plano automaticamente
- PIX no cartão do usuário: feature independente, não processado pelo SaaS

### 2.5 E-mail
- **Resend** como provedor transacional
- Casos de uso: verificação de e-mail, recuperação de senha, aviso de vencimento, formulário de contato, notificações de agendamento

### 2.6 Storage
- **Laravel Storage local** no MVP (pasta `storage/app/public`)
- Migração para S3-compatible na v1.2
- Upload máximo: 5 MB por arquivo
- Thumbnails gerados no upload (Intervention Image)

### 2.7 Fila/Jobs
- **Driver: database** — compatível com hospedagem compartilhada sem Redis
- Jobs: envio de e-mail, geração de thumbnail, webhook Efi Bank, notificações de agendamento

### 2.8 Header do cartão público
- Foto de capa ocupa 100% da largura do cartão como background do header
- Avatar (foto de perfil) sobrepõe a borda inferior da capa com borda branca
- Altura da capa: 110px fixo no mobile
- Fallback: cor primária do titular quando não há foto de capa

### 2.9 Agenda — escopo MVP
- **Agendamento completo com confirmação manual pelo titular**
- Titular define disponibilidade: dias da semana, faixas de horário, duração do slot
- Visitante escolhe dia e horário disponível, preenche dados e envia solicitação
- Titular recebe e-mail (Resend) com link rápido para confirmar ou recusar
- Slot fica bloqueado ao confirmar — não pode ser solicitado por outro visitante
- Visitante recebe e-mail de confirmação ou mensagem de recusa/reagendamento
- Confirmação automática + integração Google Calendar → v1.1

### 2.11 Analytics de cartão

- Toda visita a `/u/{slug}` registra uma linha em `card_views` com `ip_hash`, `user_agent`, `referer` e `source`
- `source` é derivado do HTTP Referer via `CardController::detectSource()`: direto / whatsapp / instagram / google / facebook / linkedin / tiktok / twitter / telegram / outros
- Clicks em links são rastreados via rota intermediária `/u/{slug}/link/{id}` que incrementa `card_links.click_count` antes de redirecionar — sem impacto perceptível na UX
- Dashboard exibe: gráfico diário 30 dias, painel de origens com percentual e cor de plataforma, ranking de links por clicks
- Não há rastreamento por cookie ou fingerprint — apenas IP hash e user agent para deduplicação básica

### 2.12 Galeria e lightbox

- Fotos da galeria exibidas em grid 3 colunas no cartão público
- Clique em qualquer foto abre lightbox fullscreen com overlay escuro
- Navegação: setas prev/next, dots indicadores de posição, swipe touch (detecção por `touchstart`/`touchend`), teclado (← → Esc)
- Dados das fotos passados ao JS via variável `const photos` renderizada pelo Blade antes do `</body>`
- Botão de excluir sempre visível como X vermelho no canto superior direito de cada foto (sem depender de hover — compatível com touch)

### 2.13 Padronização de imagens no upload

- **Foto de perfil:** orientação EXIF corrigida → crop quadrado a partir do topo → resize 400×400px → JPEG 85%
- **Foto de capa:** orientação EXIF corrigida → crop proporcional 3:1 centralizado → resize 1200×400px → JPEG 85%
- **Foto de galeria:** orientação EXIF corrigida → escala proporcional max 1200px de largura → JPEG 85%
- Todas as operações feitas no servidor via `Intervention\Image\ImageManager` com driver GD
- Ícones SVG inline nos botões de exclusão (não dependem de `lucide.createIcons()` após updates do Livewire)

### 2.14 Logo e identidade da plataforma no cartão

- Rodapé de todo cartão público exibe o logo NEXOSN (SVG 4 círculos + texto "NEX·OSN") com link para `https://nexosn.pageup.net.br`
- Usuários Free exibem adicionalmente o texto "Criado com NEXOSN" acima do logo
- Logo renderizado inline como SVG — sem dependência externa, sem carregamento assíncrono

### 2.10 Cores de marca pelo usuário
- **2 pickers independentes:** cor primária (header, fundo) + cor de botão (CTAs)
- Feature exclusiva do plano **Pro**
- No plano **Free** as cores são fixas no padrão da marca Card (`#003049` / `#D62828`)
- O painel exibe **preview ao vivo** do cartão enquanto o titular escolhe as cores
- Cores armazenadas nos campos `brand_color_primary` e `brand_color_button` na tabela `cards`
- Validação: apenas valores HEX válidos (#RRGGBB); sem transparência

---

## 3. Stack técnica — CANÔNICA

| Camada | Tecnologia | Versão mínima |
|---|---|---|
| Backend | PHP + Laravel | 8.2+ / 11 |
| Admin SaaS | Filament | 3.x |
| Frontend painel + público | Blade + Livewire + Alpine.js | Livewire 3 |
| CSS | Tailwind CSS (mobile-first) | 3.x |
| Banco de dados | MySQL | 8.0 |
| Fila | Laravel Queue driver database | — |
| E-mail | Resend | — |
| Pagamentos SaaS | Efi Bank SDK | — |
| QR Code | simplesoftwareio/simple-qrcode | — |
| Crop de imagem | Intervention Image | 3.x |
| Ícones | Lucide (CDN + NPM) | latest |
| Tipografia | Inter (Google Fonts) | — |
| Slug | spatie/laravel-sluggable | — |
| Permissões | spatie/laravel-permission | — |
| Auth | Laravel Breeze (Blade stack) | — |

---

## 4. Design System — CANÔNICO

### 4.1 Paleta de cores da marca Card

| Token CSS | Hex | Uso |
|---|---|---|
| `--color-primary` | `#003049` | Header padrão, navbar, fundo do cartão, texto forte |
| `--color-action` | `#D62828` | Botão principal (CTA) padrão |
| `--color-highlight` | `#F77F00` | PIX, botão secundário, ícone de destaque |
| `--color-accent` | `#FCBF49` | Badge Pro, subtítulo sobre fundo escuro |
| `--color-surface` | `#EAE2B7` | Rodapé do cartão, marca d'água Free |
| `--color-bg` | `#FFFFFF` | Fundo do corpo do cartão e painel |
| `--color-text` | `#1A1F2E` | Texto principal (derivado do Prussian Blue) |

### 4.2 Cores de marca personalizadas (Pro)

```
cards.brand_color_primary  → substitui --color-primary no cartão público
cards.brand_color_button   → substitui --color-action nos botões CTA

Aplicação via CSS inline no template Blade do cartão público:
  <style>
    :root {
      --card-primary: {{ $card->brand_color_primary ?? '#003049' }};
      --card-button:  {{ $card->brand_color_button  ?? '#D62828' }};
    }
  </style>

Fallback: cores padrão da marca Card quando campos são null (Free ou Pro sem customização)
```

### 4.3 Tipografia
- **Família:** Inter (Google Fonts)
- **Pesos usados:** 400 (corpo), 500 (label/destaque), 600 (heading/nome)
- **Tamanhos:**
  - Heading principal: 28px / 600
  - Nome no cartão: 16–17px / 600
  - Corpo: 13–14px / 400
  - Caption / rodapé: 11px / 400

### 4.4 Ícones
- **Biblioteca:** Lucide Icons (outline only)
- **CDN:** `https://unpkg.com/lucide@latest/dist/umd/lucide.min.js`
- **NPM:** `lucide` (para build assets)
- **Tamanhos:** 14px (inline), 16px (botões), 20px (decorativo), 24px (hero)
- **Ícones principais do produto:**
  - Cartão: `credit-card`
  - PIX: `qr-code`
  - Salvar contato: `user-plus`
  - Redes sociais: `instagram`, `linkedin`, `youtube`, `globe`
  - Contatos: `phone`, `mail`, `map-pin`
  - Compartilhar: `share-2`
  - Link: `link`
  - Arrastar: `grip-vertical`
  - Agenda: `calendar`
  - Horário: `clock`
  - Confirmar: `check-circle`
  - Recusar: `x-circle`

### 4.5 Componentes de botão (padrão)
```
Primário:   bg var(--card-button, #D62828) · text #fff · rounded-lg
Secundário: bg #F77F00 · text #fff · rounded-lg
Outline:    border var(--card-primary, #003049) · text var(--card-primary) · bg transparent · rounded-lg
```

### 4.6 Header do cartão — estrutura
```
┌─────────────────────────────────────┐
│  [foto de capa — 100% largura]      │  height: 110px
│                             [label] │  overlay escuro sutil
└──────────────────────────────────────┘
  [avatar 56px sobreposto na borda inferior, borda 3px #fff]
  Nome / Cargo / Badge Pro
  [ícones de redes sociais]
```

### 4.7 Princípios de layout
- **Mobile-first:** breakpoint base = 375px, md = 768px, lg = 1024px
- Cartão público: max-width 400px, centralizado, sem sidebar
- Painel: sidebar colapsável no mobile, drawer no md+
- Border-radius padrão: 10px cards, 8px botões, 50% avatares

---

## 5. Modelo de dados — ENTIDADES PRINCIPAIS

```
users
  id, name, email, password, email_verified_at,
  plan (enum: free|pro), plan_expires_at, trial_ends_at,
  efi_subscription_id, created_at, updated_at

cards
  id, user_id (FK), slug (unique), is_active (bool),
  display_name, title, company, bio,
  profile_photo, cover_photo, logo,
  brand_color_primary (nullable, hex),   ← NOVO
  brand_color_button (nullable, hex),    ← NOVO
  show_watermark (bool),
  contact_email, contact_phone, address, website,
  created_at, updated_at

card_links
  id, card_id (FK), type (enum: social|custom|pix|schedule),
  label, url, icon, is_active (bool), sort_order (int),
  click_count (bigint unsigned, default 0)

card_photos
  id, card_id (FK), path, thumbnail_path, alt, sort_order, created_at

contact_messages
  id, card_id (FK), sender_name, sender_email, sender_phone,
  message, read_at (nullable), created_at

card_views
  id, card_id (FK), ip_hash, user_agent, referer, source (varchar 40, default 'direct'), viewed_at

-- NOVO: Módulo Agenda --

card_schedules
  id, card_id (FK), service_name, duration_minutes (int),
  is_active (bool), created_at, updated_at

card_schedule_slots
  id, card_schedule_id (FK),
  day_of_week (enum: mon|tue|wed|thu|fri|sat|sun),
  start_time (time), end_time (time)

card_appointments
  id, card_id (FK), card_schedule_id (FK),
  appointment_date (date), appointment_time (time),
  visitor_name, visitor_email, visitor_phone,
  notes (nullable),
  status (enum: pending|confirmed|refused|cancelled),
  confirmed_at (nullable), refused_at (nullable),
  created_at, updated_at
```

---

## 6. Arquitetura de rotas

```
/                               Landing page (marketing)
/register                       Cadastro
/login                          Login
/forgot-password                Recuperação de senha
/email/verify                   Verificação de e-mail

/dashboard                      Painel — visão geral
/dashboard/card                 Editor do cartão
/dashboard/links                Gerenciar links
/dashboard/photos               Gerenciar galeria de fotos
/dashboard/contacts             Gerenciar contatos
/dashboard/schedule             Configurar agenda de disponibilidade  ← NOVO
/dashboard/appointments         Gerenciar agendamentos recebidos      ← NOVO
/dashboard/messages             Histórico de mensagens (Pro)
/dashboard/plan                 Assinatura e planos
/dashboard/settings             Configurações da conta

/u/{slug}                       Cartão público
/u/{slug}/contact               POST — formulário de contato
/u/{slug}/vcf                   Download vCard (.vcf)
/u/{slug}/agendar               GET  — tela de agendamento            ← NOVO
/u/{slug}/agendar               POST — submissão de solicitação       ← NOVO
/u/{slug}/agendar/slots         GET  — slots disponíveis (JSON/Livewire) ← NOVO

/appointments/{token}/confirm   GET — titular confirma agendamento    ← NOVO
/appointments/{token}/refuse    GET — titular recusa agendamento      ← NOVO

/admin/*                        Filament 3 — painel SaaS admin
```

---

## 7. Limites por plano

| Feature | Free | Pro |
|---|---|---|
| Links customizados | 5 | Ilimitado |
| Fotos na galeria | 3 | 30 |
| Logomarca | Não | Sim |
| Cores de marca (2 pickers) | Não — cores fixas Card | Sim |
| Agenda de agendamentos | Não | Sim |
| Histórico de mensagens | Não | Sim |
| Analytics (gráfico 30 dias, origens, clicks por link) | Sim | Sim |
| Marca d'água | Obrigatória | Removida |
| PIX no cartão | Sim | Sim |
| vCard / QR Code | Sim | Sim |
| Formulário de contato | Sim | Sim |

---

## 8. Fases de entrega

| Fase | Escopo |
|---|---|
| **MVP v1.0** | Auth completo · Header com foto de capa · Cartão com todos os blocos · Links drag-and-drop · Galeria · QR Code · vCard · Formulário de contato · Agenda com confirmação manual · Cores de marca 2 pickers (Pro) · Planos Free + Pro · Efi Bank · Admin Filament básico |
| **v1.1** | Confirmação automática de agenda · Integração Google Calendar · Analytics completo · Histórico de mensagens · Múltiplos cartões (Pro) |
| **v1.2** | OAuth Google · Subdomínio próprio (Pro) · Storage S3 |
| **v2.0** | PWA / app mobile · Marketplace de temas |

---

## 9. Convenções do projeto

- **Entry point para Claude Code:** `CLAUDE.md` na raiz
- **Skills:** `.claude/skills/`
- **Idioma:** Português BR em toda interface e código (comentários, migrations, seeders)
- **Commits:** Conventional Commits (`feat:`, `fix:`, `chore:`)
- **Migrations:** snake_case, nomes descritivos (`create_card_appointments_table`)
- **Controllers:** Resource Controllers do Laravel onde possível
- **Livewire:** componentes em `app/Livewire/`, views em `resources/views/livewire/`
- **Policies:** uma Policy por Model principal
- **Testes:** PHPUnit — ao menos Feature tests para fluxos críticos (cadastro, upgrade, cartão público, agendamento)

---

## 10. Changelog

| Versão | Data | Alterações |
|---|---|---|
| v1.0 | 2026-07-09 | Documento inicial |
| v1.1 | 2026-07-09 | + Header com foto de capa (seção 2.8) · + Módulo Agenda com confirmação manual (seção 2.9) · + Cores de marca 2 pickers Pro (seção 2.10) · + Tabelas `card_schedules`, `card_schedule_slots`, `card_appointments` · + campos `brand_color_primary`, `brand_color_button` em `cards` · + Rotas de agenda e agendamento · + Limites de plano atualizados |
| v1.2 | 2026-07-20 | + Seção 2.11 Analytics (source em card_views, click_count em card_links, dashboard 30 dias) · + Seção 2.12 Galeria lightbox (swipe, teclado, dots) · + Seção 2.13 Padronização de imagens no servidor (EXIF, crop, resize por tipo) · + Seção 2.14 Logo NEXOSN no rodapé · + Analytics movido de v1.1 para Free/Pro no MVP · + Modelo de dados atualizado (source, click_count, referer) |

---

*Documento canônico — alterações requerem revisão formal · PageUp Sistemas · 2026*
