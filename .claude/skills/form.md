# Skill: form
> Módulo M-05 · Formulário de Contato
> Leia também: CLAUDE.md · docs/design-system.md seções 5.2, 5.8

---

## Contexto do módulo

Formulário embutido no cartão público. Visitante envia mensagem ao titular.
Usa Livewire no cartão público para validação inline e toast de sucesso.
Anti-spam: honeypot + rate limiting Laravel.

---

## Migration contact_messages

```php
Schema::create('contact_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->cascadeOnDelete();
    $table->string('sender_name');
    $table->string('sender_email');
    $table->string('sender_phone')->nullable();
    $table->text('message');
    $table->string('ip_address', 45)->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamps();

    $table->index(['card_id', 'created_at']);
});
```

---

## Livewire: ContactForm (cartão público)

```php
// app/Livewire/Card/ContactForm.php
class ContactForm extends Component
{
    public Card $card;
    public bool $sent = false;

    // Campos do formulário
    #[Validate('required|string|max:100')]
    public string $senderName  = '';

    #[Validate('required|email|max:150')]
    public string $senderEmail = '';

    #[Validate('nullable|string|max:20')]
    public string $senderPhone = '';

    #[Validate('required|string|min:10|max:1000')]
    public string $message = '';

    // Anti-spam honeypot — deve estar vazio
    public string $website = '';

    public function submit(Request $request): void
    {
        // Honeypot: campo oculto preenchido = bot
        if (!empty($this->website)) {
            $this->sent = true; // Simula sucesso para o bot
            return;
        }

        // Rate limiting: 3 envios por hora por IP
        $key = 'contact-form:' . ($request->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('limit', 'Muitas tentativas. Aguarde antes de enviar novamente.');
            return;
        }
        RateLimiter::hit($key, 3600);

        $this->validate();

        $msg = ContactMessage::create([
            'card_id'      => $this->card->id,
            'sender_name'  => $this->senderName,
            'sender_email' => $this->senderEmail,
            'sender_phone' => $this->senderPhone,
            'message'      => $this->message,
            'ip_address'   => $request->ip(),
        ]);

        SendContactMessage::dispatch($msg)->onQueue('default');

        $this->reset(['senderName', 'senderEmail', 'senderPhone', 'message']);
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.card.contact-form', ['card' => $this->card]);
    }
}
```

### View do ContactForm (no cartão público)

```blade
{{-- resources/views/livewire/card/contact-form.blade.php --}}
<div class="px-[18px] py-[14px] bg-[#F9F9F7]">
  <div class="flex items-center gap-1.5 text-[13px] font-semibold mb-3"
       style="color: var(--card-primary)">
    <svg data-lucide="send" class="w-4 h-4"></svg>
    Enviar mensagem
  </div>

  @if($sent)
    <div class="flex flex-col items-center gap-2 py-6 text-center">
      <div class="w-10 h-10 rounded-full bg-[#D1FAE5] flex items-center justify-center">
        <svg data-lucide="check-circle" class="w-5 h-5 text-[#065F46]"></svg>
      </div>
      <p class="text-[13px] font-medium text-[#222]">Mensagem enviada!</p>
      <p class="text-[12px] text-[#888]">{{ $card->display_name }} receberá em breve.</p>
      <button wire:click="$set('sent', false)"
              class="text-[12px] text-[#003049] underline mt-1">
        Enviar outra mensagem
      </button>
    </div>
  @else
    <form wire:submit="submit" class="flex flex-col gap-2">

      {{-- Honeypot (oculto de humanos, visível para bots) --}}
      <div class="hidden" aria-hidden="true">
        <input wire:model="website" type="text" name="website" tabindex="-1" autocomplete="off">
      </div>

      @error('limit')
      <div class="flex items-center gap-2 bg-[#FEF3C7] border border-[#FCD34D] rounded-[8px] p-2.5 mb-1">
        <svg data-lucide="alert-triangle" class="w-4 h-4 text-[#D97706]"></svg>
        <p class="text-[11px] text-[#92400E]">{{ $message }}</p>
      </div>
      @enderror

      <input wire:model="senderName" type="text" placeholder="Seu nome"
             class="w-full border border-[#ccc] rounded-[8px] px-[11px] py-[9px]
                    text-[12px] font-['Inter'] bg-white focus:outline-none
                    focus:border-[var(--card-primary)]
                    @error('senderName') border-[#D62828] @enderror">
      @error('senderName')<p class="text-[11px] text-[#D62828] -mt-1">{{ $message }}</p>@enderror

      <input wire:model="senderEmail" type="email" placeholder="Seu e-mail"
             class="w-full border border-[#ccc] rounded-[8px] px-[11px] py-[9px]
                    text-[12px] font-['Inter'] bg-white focus:outline-none
                    focus:border-[var(--card-primary)]">
      @error('senderEmail')<p class="text-[11px] text-[#D62828] -mt-1">{{ $message }}</p>@enderror

      <input wire:model="senderPhone" type="tel" placeholder="Telefone (opcional)"
             class="w-full border border-[#ccc] rounded-[8px] px-[11px] py-[9px]
                    text-[12px] font-['Inter'] bg-white focus:outline-none">

      <textarea wire:model="message" placeholder="Sua mensagem" rows="3"
                class="w-full border border-[#ccc] rounded-[8px] px-[11px] py-[9px]
                       text-[12px] font-['Inter'] resize-none bg-white focus:outline-none
                       focus:border-[var(--card-primary)]
                       @error('message') border-[#D62828] @enderror"></textarea>
      @error('message')<p class="text-[11px] text-[#D62828] -mt-1">{{ $message }}</p>@enderror

      <button type="submit"
              class="flex items-center justify-center gap-2 w-full py-[10px]
                     rounded-[10px] text-[13px] font-medium text-white
                     font-['Inter'] mt-1"
              style="background-color: var(--card-button)"
              wire:loading.attr="disabled">
        <span wire:loading.remove>
          <svg data-lucide="send" class="w-4 h-4 inline -mt-0.5"></svg>
          Enviar mensagem
        </span>
        <span wire:loading class="flex items-center gap-2">
          <svg class="w-4 h-4 animate-spin border-2 border-white border-t-transparent rounded-full"></svg>
          Enviando...
        </span>
      </button>
    </form>
  @endif
</div>
```

---

## Job: SendContactMessage

```php
// app/Jobs/SendContactMessage.php
class SendContactMessage implements ShouldQueue
{
    public function __construct(public readonly ContactMessage $message) {}

    public function handle(): void
    {
        $card  = $this->message->card()->with('user')->first();
        $owner = $card->user;

        Mail::to($owner->email)
            ->send(new ContactMessageMail($this->message, $card));
    }
}
```

### Template de e-mail

```php
// app/Mail/ContactMessageMail.php
class ContactMessageMail extends Mailable
{
    public function __construct(
        public readonly ContactMessage $msg,
        public readonly Card $card
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Card] Nova mensagem de {$this->msg->sender_name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-message');
    }
}
```

```blade
{{-- resources/views/emails/contact-message.blade.php --}}
@component('mail::message')
# Nova mensagem recebida no seu cartão

**De:** {{ $msg->sender_name }} ({{ $msg->sender_email }})
@if($msg->sender_phone)
**Telefone:** {{ $msg->sender_phone }}
@endif

**Mensagem:**

{{ $msg->message }}

---
*Enviado via card.app/u/{{ $card->slug }}*
@endcomponent
```

---

## Histórico de mensagens (Pro)

```php
// app/Livewire/Card/MessageList.php — rota protegida por plan:messages
class MessageList extends Component
{
    public function markRead(int $id): void
    {
        auth()->user()->card->messages()->findOrFail($id)->update(['read_at' => now()]);
    }

    public function render(): View
    {
        $messages = auth()->user()->card->messages()
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('livewire.card.message-list', compact('messages'));
    }
}
```

---

## Checklist de entrega (T-057 a T-065)

- [ ] Migration `contact_messages` criada e rodada
- [ ] Model `ContactMessage` com fillable e relacionamento com `Card`
- [ ] Livewire `ContactForm` com honeypot e rate limit
- [ ] Rate limiting: 3 envios/hora por IP via `RateLimiter`
- [ ] Estado de sucesso após envio (substituir form por mensagem)
- [ ] Job `SendContactMessage` disparado na fila `default`
- [ ] Template de e-mail renderizando corretamente (Resend + Blade)
- [ ] View de histórico de mensagens no painel (Pro only, CheckPlan)
- [ ] Teste: enviar formulário → verificar e-mail recebido → verificar deduplicação de spam
