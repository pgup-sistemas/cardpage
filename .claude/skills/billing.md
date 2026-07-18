# Skill: billing
> Módulo M-08 · Planos e Billing · Efi Bank
> Leia também: CLAUDE.md · docs/constitution.md seções 2.3 e 2.4 · docs/design-system.md

---

## Contexto do módulo

Receita do SaaS via Efi Bank (ex-Gerencianet) — integração já existente na conta PageUp.
Planos: Free (gratuito) e Pro (mensal/anual).
Trial de 14 dias do Pro — sem cartão obrigatório.
Webhooks ativam/suspendem o plano automaticamente.

---

## PlanService — core da lógica de planos

```php
// app/Services/PlanService.php
class PlanService
{
    /** Features disponíveis por plano */
    private array $proFeatures = [
        'colors',       // Cores de marca personalizadas
        'logo',         // Logomarca
        'agenda',       // Módulo de agenda
        'messages',     // Histórico de mensagens
        'unlimited_links', // Links ilimitados
        'no_watermark', // Remove marca d'água
    ];

    /** Limites por recurso no plano Free */
    private array $freeLimits = [
        'links'  => 5,
        'photos' => 3,
    ];

    public function can(User $user, string $feature): bool
    {
        if ($this->isPro($user)) return true;
        return !in_array($feature, $this->proFeatures);
    }

    public function withinLimit(User $user, string $resource): bool
    {
        if ($this->isPro($user)) return true;
        $limit = $this->freeLimits[$resource] ?? PHP_INT_MAX;
        return $this->currentCount($user, $resource) < $limit;
    }

    public function currentCount(User $user, string $resource): int
    {
        return match($resource) {
            'links'  => $user->card->links()->count(),
            'photos' => $user->card->photos()->count(),
            default  => 0,
        };
    }

    public function isPro(User $user): bool
    {
        // Pro ativo por assinatura
        if ($user->plan === 'pro' && $user->plan_expires_at?->isFuture()) return true;
        // Trial ativo
        if ($user->trial_ends_at?->isFuture()) return true;
        return false;
    }

    public function activateTrial(User $user): void
    {
        $user->update([
            'plan'            => 'free', // Plano base continua free
            'trial_ends_at'   => now()->addDays(14),
        ]);
    }

    public function activate(User $user, string $subscriptionId, int $days = 30): void
    {
        $user->update([
            'plan'                  => 'pro',
            'plan_expires_at'       => now()->addDays($days),
            'efi_subscription_id'   => $subscriptionId,
            'trial_ends_at'         => null, // Encerra trial
        ]);
    }

    public function downgrade(User $user): void
    {
        $user->update([
            'plan'             => 'free',
            'plan_expires_at'  => null,
        ]);
        // Mostrar watermark novamente
        $user->card?->update(['show_watermark' => true]);
    }
}
```

---

## EfiBankService

```php
// app/Services/EfiBankService.php
// Usar a SDK oficial: composer require efipay/sdk-php-apis-efi
use Efi\EfiPay;

class EfiBankService
{
    private EfiPay $client;

    public function __construct()
    {
        $this->client = new EfiPay([
            'client_id'     => config('services.efibank.client_id'),
            'client_secret' => config('services.efibank.client_secret'),
            'sandbox'       => config('services.efibank.sandbox', false),
            'certificate'   => config('services.efibank.certificate'),
        ]);
    }

    /** Cria assinatura recorrente */
    public function createSubscription(User $user, string $planType = 'monthly'): array
    {
        // Implementar conforme documentação Efi Bank para cobranças recorrentes
        // Retorna ['subscription_id' => '...', 'payment_url' => '...']
        return $this->client->createSubscription([], [
            'plan'     => ['id' => $this->getPlanId($planType)],
            'customer' => [
                'name'     => $user->name,
                'email'    => $user->email,
                'cpf'      => '', // Solicitar no fluxo de checkout
            ],
        ]);
    }

    /** Cancela assinatura */
    public function cancelSubscription(User $user): bool
    {
        if (!$user->efi_subscription_id) return false;
        $this->client->cancelSubscription(['id' => $user->efi_subscription_id]);
        return true;
    }

    private function getPlanId(string $type): int
    {
        return match($type) {
            'annual'  => (int) config('services.efibank.plan_annual_id'),
            default   => (int) config('services.efibank.plan_monthly_id'),
        };
    }
}
```

### .env de billing

```dotenv
EFIBANK_CLIENT_ID=seu_client_id
EFIBANK_CLIENT_SECRET=seu_client_secret
EFIBANK_SANDBOX=true
EFIBANK_CERTIFICATE=storage/app/efibank/certificate.pem
EFIBANK_PLAN_MONTHLY_ID=123
EFIBANK_PLAN_ANNUAL_ID=456
EFIBANK_WEBHOOK_SECRET=seu_webhook_secret
```

---

## Webhook Controller

```php
// app/Http/Controllers/EfiBankWebhookController.php
class EfiBankWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Validar assinatura do webhook (HMAC ou IP whitelist)
        // Documentação Efi Bank: verificar header ou secret

        $type           = $payload['type'] ?? null;
        $subscriptionId = $payload['subscription']['id'] ?? null;

        $user = User::where('efi_subscription_id', $subscriptionId)->first();
        if (!$user) return response()->json(['ok' => false], 404);

        $planService = app(PlanService::class);

        match($type) {
            'subscription_paid'    => $planService->activate($user, $subscriptionId),
            'subscription_expired',
            'subscription_canceled' => $planService->downgrade($user),
            default                => null,
        };

        return response()->json(['ok' => true]);
    }
}
```

### Rota sem CSRF

```php
// routes/web.php
Route::post('/webhook/efibank', [EfiBankWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.efibank');
```

---

## View de planos (/dashboard/plan)

```blade
{{-- resources/views/dashboard/plan.blade.php --}}
@extends('layouts.app')
@section('title', 'Meu plano')
@section('content')
<div>
  {{-- Header --}}
  <div class="flex items-center gap-2 px-4 py-3.5 bg-white border-b border-[#E0E0DE]
              text-[14px] font-semibold text-[#222]">
    <svg data-lucide="star" class="w-4 h-4 text-[#003049]"></svg>
    Assinatura e planos
  </div>

  <div class="p-4 flex flex-col gap-4">

    {{-- Plano atual --}}
    <div class="bg-[#EBEBEA] rounded-[12px] p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <p class="text-[11px] text-[#888] mb-0.5">Plano atual</p>
          <p class="text-[16px] font-semibold text-[#222]">
            @if($isPro) Pro @elseif($inTrial) Trial Pro @else Free @endif
          </p>
        </div>
        @if($isPro)
          <span class="bg-[#FCBF49] text-[#003049] text-[10px] font-semibold px-3 py-1 rounded-full">PRO</span>
        @endif
      </div>

      @if($inTrial)
      <div class="flex items-center gap-2 bg-[#FEF3C7] rounded-[8px] p-2.5 text-[12px] text-[#92400E]">
        <svg data-lucide="clock" class="w-4 h-4 flex-shrink-0"></svg>
        Trial encerra em {{ $user->trial_ends_at->diffForHumans() }}
      </div>
      @endif

      @if($isPro && $user->plan_expires_at)
      <div class="text-[12px] text-[#666] mt-2">
        Próxima renovação: {{ $user->plan_expires_at->format('d/m/Y') }}
      </div>
      @endif
    </div>

    {{-- Cards de plano --}}
    <div class="grid grid-cols-1 gap-3">

      {{-- Free --}}
      <div class="border {{ !$isPro && !$inTrial ? 'border-[#003049]' : 'border-[#E0E0DE]' }}
                  rounded-[12px] p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-[14px] font-semibold text-[#222]">Free</p>
            <p class="text-[20px] font-medium text-[#222]">R$ 0 <span class="text-[12px] text-[#888]">/mês</span></p>
          </div>
          @if(!$isPro && !$inTrial)
            <span class="text-[10px] font-semibold text-[#003049] border border-[#003049] px-2 py-0.5 rounded-full">
              Plano atual
            </span>
          @endif
        </div>
        <ul class="flex flex-col gap-1.5 text-[12px] text-[#666]">
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>1 cartão digital</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>Até 5 links</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>PIX e QR Code</li>
          <li class="flex items-center gap-2"><svg data-lucide="x" class="w-3.5 h-3.5 text-[#ccc]"></svg>Cores personalizadas</li>
          <li class="flex items-center gap-2"><svg data-lucide="x" class="w-3.5 h-3.5 text-[#ccc]"></svg>Agenda</li>
          <li class="flex items-center gap-2 text-[#aaa]"><svg data-lucide="credit-card" class="w-3.5 h-3.5"></svg>Marca d'água obrigatória</li>
        </ul>
      </div>

      {{-- Pro --}}
      <div class="border-2 border-[#FCBF49] rounded-[12px] p-4 relative overflow-hidden">
        <div class="absolute top-0 right-0 bg-[#FCBF49] text-[#003049] text-[10px] font-semibold
                    px-3 py-1 rounded-bl-[10px]">
          RECOMENDADO
        </div>
        <div class="flex items-center justify-between mb-3 mt-2">
          <div>
            <p class="text-[14px] font-semibold text-[#222]">Pro</p>
            <p class="text-[20px] font-medium text-[#222]">
              R$ 19,90 <span class="text-[12px] text-[#888]">/mês</span>
            </p>
            <p class="text-[11px] text-[#F77F00]">ou R$ 179,90/ano — economize 25%</p>
          </div>
        </div>
        <ul class="flex flex-col gap-1.5 text-[12px] text-[#666] mb-4">
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>Tudo do Free</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>Links ilimitados</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>Cores de marca personalizadas</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>Agenda com agendamentos</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>30 fotos na galeria</li>
          <li class="flex items-center gap-2"><svg data-lucide="check" class="w-3.5 h-3.5 text-[#16A34A]"></svg>Sem marca d'água</li>
        </ul>

        @if(!$isPro)
        <a href="{{ route('dashboard.checkout', 'monthly') }}"
           class="flex items-center justify-center gap-2 w-full py-[11px]
                  rounded-[10px] bg-[#D62828] text-white text-[13px] font-medium no-underline">
          <svg data-lucide="star" class="w-4 h-4"></svg>
          Assinar Pro — R$ 19,90/mês
        </a>
        <a href="{{ route('dashboard.checkout', 'annual') }}"
           class="flex items-center justify-center gap-2 w-full mt-2 py-[9px]
                  rounded-[10px] border border-[#003049] text-[#003049] text-[12px] font-medium no-underline">
          Assinar anual — R$ 179,90/ano
        </a>
        @endif
      </div>

    </div>
  </div>
</div>
@endsection
```

---

## Jobs de aviso de vencimento

```php
// app/Console/Commands/SendPlanExpirationWarnings.php
class SendPlanExpirationWarnings extends Command
{
    protected $signature = 'billing:expiration-warnings';

    public function handle(): void
    {
        // 3 dias antes
        $expiring3d = User::where('plan', 'pro')
            ->whereDate('plan_expires_at', now()->addDays(3))
            ->get();
        foreach ($expiring3d as $user) {
            Mail::to($user->email)->queue(new PlanExpiringMail($user, 3));
        }

        // No dia
        $expiringToday = User::where('plan', 'pro')
            ->whereDate('plan_expires_at', today())
            ->get();
        foreach ($expiringToday as $user) {
            Mail::to($user->email)->queue(new PlanExpiredMail($user));
        }

        // 3 dias em atraso → downgrade
        $overdue = User::where('plan', 'pro')
            ->whereDate('plan_expires_at', now()->subDays(3))
            ->get();
        foreach ($overdue as $user) {
            app(PlanService::class)->downgrade($user);
            Mail::to($user->email)->queue(new PlanDowngradedMail($user));
        }
    }
}
```

```php
// routes/console.php
Schedule::command('billing:expiration-warnings')->dailyAt('09:00');
```

---

## CheckPlan Middleware

```php
// app/Http/Middleware/CheckPlan.php
class CheckPlan
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        if (!app(PlanService::class)->can($user, $feature)) {
            if ($request->expectsJson()) {
                return response()->json(['erro' => 'Recurso disponível apenas no plano Pro.'], 403);
            }
            return redirect()->route('dashboard.plan')
                ->with('aviso_upgrade', 'Este recurso requer o plano Pro. Faça upgrade para continuar.');
        }
        return $next($request);
    }
}
```

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['plan' => CheckPlan::class]);
})
```

---

## Checklist de entrega (T-072 a T-079)

- [ ] `PlanService` completo com todos os métodos
- [ ] `EfiBankService` integrado com SDK Efi Bank
- [ ] `EfiBankWebhookController` processando `subscription_paid` e cancelamentos
- [ ] Rota webhook fora do CSRF
- [ ] View `/dashboard/plan` com cards Free e Pro
- [ ] Flows de checkout (mensal e anual)
- [ ] `CheckPlan` middleware registrado e funcional em todas as rotas Pro
- [ ] Comando `billing:expiration-warnings` agendado
- [ ] E-mails de vencimento (3d antes, no dia, 3d depois)
- [ ] Downgrade automático com preservação de dados
- [ ] Teste: simular webhook → verificar ativação/downgrade de plano
