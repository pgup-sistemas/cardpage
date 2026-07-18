# Skill: agenda
> Módulo M-10 · Agenda e Agendamentos (Pro)
> Leia também: CLAUDE.md · docs/constitution.md seção 2.9 · docs/arquitetura.md seções 2 e 4

---

## Contexto do módulo

Feature exclusiva do plano Pro. Permite ao titular configurar disponibilidade semanal e
receber solicitações de agendamento de visitantes, com confirmação manual via e-mail ou painel.

Decisão canônica (constitution.md 2.9): confirmação automática e Google Calendar são v1.1.

---

## Tabelas envolvidas

```
card_schedules         → serviço + duração + ativo/inativo
card_schedule_slots    → disponibilidade por dia da semana + faixa de horário
card_appointments      → solicitações com status + token de confirmação
```

Ver migrations completas em: docs/arquitetura.md seção 2.

---

## AppointmentService — métodos obrigatórios

```php
// Gera array de slots para uma data baseado nos slots configurados
// Exclui slots com status 'confirmed' naquela data/horário
public function availableSlots(CardSchedule $schedule, Carbon $date): array

// Verifica se um slot específico está livre
public function isSlotAvailable(CardSchedule $schedule, Carbon $date, string $time): bool

// Cria solicitação com token único (Str::uuid())
public function createRequest(Card $card, array $data): CardAppointment

// Confirma e bloqueia o slot
public function confirm(CardAppointment $appointment): void

// Recusa e libera o slot (não altera outros pending)
public function refuse(CardAppointment $appointment): void
```

---

## Fluxo de e-mails (Resend)

1. Solicitação criada → Job `SendAppointmentNotification` → e-mail para titular
   - Contém: dados do visitante + links `/appointments/{token}/confirm` e `/appointments/{token}/refuse`
   - Token expira em 7 dias

2. Titular confirma → e-mail `AppointmentConfirmed` para visitante
   - Contém: data, hora, nome do titular, e-mail e telefone de contato

3. Titular recusa → e-mail `AppointmentRefused` para visitante
   - Mensagem padrão + convite para escolher outro horário

---

## Componentes Livewire

### ScheduleConfig (painel)
- Campos: nome do serviço, duração do slot (select: 30min, 1h, 1h30, 2h)
- Checkboxes de dias da semana
- Para cada dia ativo: inputs de horário início e fim
- Botão salvar + toggle ativar/desativar agenda

### AppointmentCalendar (cartão público)
- Calendário mensal com dias disponíveis destacados
- Ao clicar no dia: carrega slots via endpoint JSON `/u/{slug}/agendar/slots?date=`
- Ao clicar no slot: exibe formulário de solicitação inline
- Após envio: toast de confirmação

---

## Checklist de entrega (T-086 a T-100)

- [ ] Migrations criadas e rodadas
- [ ] Models com relacionamentos e casts
- [ ] AppointmentService completo com testes unitários
- [ ] ScheduleConfig funcional no painel (Pro only via CheckPlan)
- [ ] Endpoint JSON de slots retornando corretamente
- [ ] AppointmentCalendar renderizando no cartão público
- [ ] Formulário de solicitação funcional
- [ ] Jobs de e-mail disparando corretamente
- [ ] Rotas de token funcionando (confirm/refuse)
- [ ] Slot bloqueado após confirmação
- [ ] Slot liberado após recusa
- [ ] Token com expiração de 7 dias validada
- [ ] Feature test completo do fluxo de agendamento

---

## Armadilhas comuns

- Não confundir `card_schedule_slots` (disponibilidade recorrente semanal) com
  `card_appointments` (ocorrências específicas confirmadas)
- Sempre usar `Carbon::parse()` para comparar datas e horários
- Slots devem considerar o fuso horário do servidor (UTC) — exibir no fuso do usuário é v1.1
- Nunca reutilizar token — gerar novo `Str::uuid()` em cada solicitação
- Bloquear rota de agendamento público se `card->schedule->is_active === false`

