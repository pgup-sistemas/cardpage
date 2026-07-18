# Card — Tasks MVP v1.0
> Breakdown atômico de implementação · 2026-07-09
> Execute uma fase por vez. Aguarde aprovação antes de avançar.

---

## Fase 0 · Scaffold e configuração

- [ ] `T-001` Criar projeto Laravel 11: `composer create-project laravel/laravel card`
- [ ] `T-002` Instalar Laravel Breeze (Blade + Livewire): `composer require laravel/breeze && php artisan breeze:install livewire`
- [ ] `T-003` Instalar Filament 3: `composer require filament/filament && php artisan filament:install --panels`
- [ ] `T-004` Instalar pacotes: `spatie/laravel-sluggable`, `spatie/laravel-permission`, `simplesoftwareio/simple-qrcode`, `intervention/image-laravel`
- [ ] `T-005` Instalar Lucide via NPM: `npm install lucide`
- [ ] `T-006` Configurar `.env`: DB, Resend (MAIL_MAILER=resend), queue driver=database, APP_URL
- [ ] `T-007` Criar layout `card.blade.php` com suporte a cores dinâmicas via CSS vars
- [ ] `T-008` Configurar Tailwind com Inter (Google Fonts) e variáveis CSS customizadas
- [ ] `T-009` Rodar migrations iniciais do Breeze + criar migration de planos em `users`
- [ ] `T-010` Criar `PlanSeeder` com Free e Pro + rodar seeders

---

## Fase 1 · Auth e conta (M-01)

- [ ] `T-011` Personalizar telas Breeze (cadastro, login, recuperação) com identidade Card
- [ ] `T-012` Adicionar campo `slug` no cadastro — gerado automaticamente do nome, validado único
- [ ] `T-013` Criar Card automaticamente ao verificar e-mail (observer em User)
- [ ] `T-014` Ativar trial de 14 dias ao criar conta (PlanService::activateTrial)
- [ ] `T-015` Criar middleware `CheckPlan` com mapeamento de features
- [ ] `T-016` Criar rota e view de exclusão de conta com apagamento de dados (LGPD)
- [ ] `T-017` Teste Feature: cadastro → verificação → login → painel

---

## Fase 2 · Cartão público — estrutura base (M-02)

- [ ] `T-018` Migration `cards` com todos os campos (ver arquitetura.md seção 2)
- [ ] `T-019` Model `Card` com fillable, casts, accessors de cor e relacionamentos
- [ ] `T-020` Route e Controller `CardController@show` para `/u/{slug}`
- [ ] `T-021` View `card/show.blade.php` estendendo `layouts/card.blade.php`
- [ ] `T-022` Bloco header: foto de capa como background, avatar sobreposto, nome, cargo
- [ ] `T-023` Bloco bio: texto "Sobre" com suporte a quebra de linha
- [ ] `T-024` Bloco redes sociais: ícones Lucide detectados automaticamente
- [ ] `T-025` Bloco contatos: telefone (WhatsApp), e-mail, endereço (Maps), site
- [ ] `T-026` Bloco links customizados: lista de botões com ícone e label
- [ ] `T-027` Bloco PIX: botão + QR Code PIX (QrCodeService)
- [ ] `T-028` Bloco galeria: thumbnails em grid responsivo
- [ ] `T-029` Rodapé com marca d'água condicional (show_watermark)
- [ ] `T-030` Meta tags SEO e Open Graph no `<head>`
- [ ] `T-031` Teste: acessar `/u/slug-teste` e validar todos os blocos

---

## Fase 3 · Painel — editor do cartão (M-07 + M-02)

- [ ] `T-032` Layout `app.blade.php` com sidebar mobile-first (Alpine.js toggle)
- [ ] `T-033` Componente Livewire `CardEditor` — formulário principal
- [ ] `T-034` Upload foto de perfil com Cropper.js + ImageService (crop circular)
- [ ] `T-035` Upload foto de capa com preview imediato
- [ ] `T-036` Componente Livewire `ColorPicker` — 2 pickers com preview ao vivo (Pro)
- [ ] `T-037` Componente Livewire `CardPreview` — preview do cartão em tempo real
- [ ] `T-038` Dashboard `Overview` — contador de visualizações, link para compartilhar
- [ ] `T-039` Botão "Compartilhar" com modal: QR Code, copiar link, download QR (M-06)
- [ ] `T-040` Contador de views: registrar acesso em `card_views` via job assíncrono
- [ ] `T-041` Teste Livewire: editar campos e verificar preview ao vivo

---

## Fase 4 · Links e Redes Sociais (M-03)

- [ ] `T-042` Migration `card_links`
- [ ] `T-043` Model `CardLink` com fillable e relacionamento
- [ ] `T-044` Componente Livewire `LinkManager` — CRUD de links
- [ ] `T-045` `SocialLinkService::detectIcon` — mapeamento URL → ícone Lucide
- [ ] `T-046` Drag-and-drop de links com Alpine.js (Sortable.js via NPM)
- [ ] `T-047` Toggle ativo/inativo por link
- [ ] `T-048` Validação de limite de links por plano (PlanService::withinLimit)
- [ ] `T-049` Exibição dos links no cartão público com ícone, label e botão
- [ ] `T-050` Teste: criar 6 links no Free → verificar bloqueio no 6º

---

## Fase 5 · Contatos e vCard (M-04)

- [ ] `T-051` Campos de contato no `CardEditor` (telefone, e-mail, endereço, site)
- [ ] `T-052` `VCardService::generate` — gerar .vcf 3.0 com todos os campos + foto
- [ ] `T-053` Rota `/u/{slug}/vcf` com download do arquivo
- [ ] `T-054` Botão "Salvar Contato" no cartão público com ícone `user-plus`
- [ ] `T-055` Botão WhatsApp com `wa.me/` + número formatado (remover não-dígitos)
- [ ] `T-056` Teste: baixar .vcf e importar no contatos do celular

---

## Fase 6 · Formulário de contato (M-05)

- [ ] `T-057` Migration `contact_messages`
- [ ] `T-058` Model `ContactMessage`
- [ ] `T-059` Componente Livewire ou form Blade no cartão público
- [ ] `T-060` Validação: honeypot + throttle 3 envios/hora por IP
- [ ] `T-061` Job `SendContactMessage` → e-mail Resend para o titular
- [ ] `T-062` Template de e-mail `emails/contact-message.blade.php`
- [ ] `T-063` Toast de confirmação após envio (Alpine.js)
- [ ] `T-064` View de histórico de mensagens no painel (Pro — CheckPlan)
- [ ] `T-065` Teste: enviar formulário e verificar e-mail recebido

---

## Fase 7 · QR Code e Compartilhamento (M-06)

- [ ] `T-066` `QrCodeService::forCard` — QR Code da URL do cartão
- [ ] `T-067` `QrCodeService::forPix` — QR Code EMV/BR Code da chave PIX
- [ ] `T-068` Download QR Code em PNG (response com content-type image/png)
- [ ] `T-069` Download QR Code em SVG
- [ ] `T-070` Modal de compartilhamento no painel: QR, copiar link, download
- [ ] `T-071` Open Graph: gerar imagem OG dinâmica ou usar foto de perfil como fallback

---

## Fase 8 · Billing e Planos (M-08)

- [ ] `T-072` `EfiBankService` — wrapper da SDK com métodos: createSubscription, cancelSubscription, getStatus
- [ ] `T-073` Controller `EfiBankWebhookController` — processar eventos de pagamento
- [ ] `T-074` Job de upgrade: ativar Pro ao receber webhook de pagamento confirmado
- [ ] `T-075` Job de downgrade: reverter para Free após inadimplência
- [ ] `T-076` View `/dashboard/plan` — plano atual, data de vencimento, botão upgrade
- [ ] `T-077` Fluxo de upgrade: PIX, boleto, cartão via Efi Bank
- [ ] `T-078` Scheduled job: enviar e-mails de aviso de vencimento (3d antes, no dia, 3d depois)
- [ ] `T-079` Teste: simular webhook de pagamento e verificar ativação do Pro

---

## Fase 9 · Admin Filament (M-09)

- [ ] `T-080` `UserResource` — listagem com busca, filtro por plano, ações: suspender, impersonar
- [ ] `T-081` `CardResource` — listagem de cartões, ver/suspender
- [ ] `T-082` `PlanResource` — CRUD de planos e preços
- [ ] `T-083` Dashboard Filament com widgets: total usuários, MRR, cartões, conversão
- [ ] `T-084` Log de auditoria: impersonation registrada com IP e timestamp
- [ ] `T-085` Teste: acessar /admin e verificar permissões de admin

---

## Fase 10 · Agenda (M-10) — Pro

- [ ] `T-086` Migrations: `card_schedules`, `card_schedule_slots`, `card_appointments`
- [ ] `T-087` Models com relacionamentos e casts
- [ ] `T-088` `AppointmentService` completo (availableSlots, isSlotAvailable, createRequest, confirm, refuse)
- [ ] `T-089` Componente Livewire `ScheduleConfig` — painel de configuração de disponibilidade
- [ ] `T-090` Componente Livewire `AppointmentCalendar` — calendário público no cartão
- [ ] `T-091` Endpoint JSON `/u/{slug}/agendar/slots?date=YYYY-MM-DD` — retorna slots disponíveis
- [ ] `T-092` Formulário de solicitação no cartão público (nome, e-mail, telefone, observação)
- [ ] `T-093` Job `SendAppointmentNotification` — e-mail para titular com links confirm/recusar
- [ ] `T-094` Rotas de token: `/appointments/{token}/confirm` e `/appointments/{token}/refuse`
- [ ] `T-095` E-mail de confirmação para visitante (Resend)
- [ ] `T-096` E-mail de recusa para visitante (Resend)
- [ ] `T-097` View `/dashboard/appointments` — lista com filtro por status
- [ ] `T-098` Bloco "Agendar" visível no cartão público apenas quando agenda está ativa e plano é Pro
- [ ] `T-099` Expiração de token de confirmação (7 dias via Carbon)
- [ ] `T-100` Teste completo: configurar agenda → solicitar → confirmar → verificar e-mails

---

## Fase 11 · Qualidade e deploy

- [ ] `T-101` Escrever Feature tests para fluxos críticos (U-01, C-01, B-02, AG-05)
- [ ] `T-102` Configurar `php artisan schedule:run` no cron do servidor compartilhado
- [ ] `T-103` Configurar `php artisan queue:work` via supervisor ou cron fallback
- [ ] `T-104` Otimizar imagens do cartão público com lazy loading
- [ ] `T-105` Configurar `.htaccess` para servidor compartilhado (public/ como webroot)
- [ ] `T-106` Configurar variáveis de ambiente de produção (.env.production)
- [ ] `T-107` Rodar `php artisan optimize` e `npm run build`
- [ ] `T-108` Teste de carga básico: acessar cartão público 100x e medir tempo de resposta
- [ ] `T-109` Validar LGPD: link para política de privacidade, banner de cookies, exclusão de conta
- [ ] `T-110` Deploy final + smoke test completo

---

## Ordem de execução recomendada

```
Fase 0 → Fase 1 → Fase 2 → Fase 3 → Fase 4 → Fase 5
→ Fase 6 → Fase 7 → Fase 8 → Fase 9 → Fase 10 → Fase 11
```

Cada fase termina com ao menos um teste antes de avançar.
Fases 8 e 9 podem rodar em paralelo após a Fase 7.
Fase 10 depende da Fase 3 (painel) estar completa.

---

*Tasks v1.0 · Card SaaS · PageUp Sistemas · 2026*
