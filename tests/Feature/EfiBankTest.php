<?php

namespace Tests\Feature;

use App\Mail\PlanDowngradedMail;
use App\Models\User;
use App\Services\EfiBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa EfiBankService e os controllers que dependem dele usando uma subclasse
 * que sobrescreve as chamadas reais ao SDK (fetchCheckoutLink/fetchNotification/
 * fetchSubscriptionDetail) — não há credenciais Efi para bater na API de verdade.
 */
class EfiBankTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithCard(string $plan = 'free'): User
    {
        $user = User::factory()->create(['plan' => $plan]);
        $user->card()->create([
            'slug'         => 'titular-' . $user->id,
            'display_name' => 'Titular Teste',
            'is_active'    => true,
        ]);
        return $user->fresh();
    }

    // ── createCheckoutLink ───────────────────────────────────────────────────

    public function test_checkout_link_lanca_excecao_sem_plan_id_configurado(): void
    {
        config(['services.efibank.plan_id_monthly' => null]);
        $user = $this->makeUserWithCard();

        $service = new EfiBankService();

        $this->expectException(RuntimeException::class);
        $service->createCheckoutLink($user, 'monthly');
    }

    public function test_checkout_link_retorna_payment_url(): void
    {
        config(['services.efibank.plan_id_monthly' => 123]);
        $user = $this->makeUserWithCard();

        $service = new class extends EfiBankService {
            protected function fetchCheckoutLink(array $params, array $body): array
            {
                return ['data' => ['payment_url' => 'https://pagamento.gerencianet.com.br/abc123']];
            }
        };

        $url = $service->createCheckoutLink($user, 'monthly');

        $this->assertSame('https://pagamento.gerencianet.com.br/abc123', $url);
    }

    public function test_checkout_link_lanca_excecao_se_api_nao_retorna_url(): void
    {
        config(['services.efibank.plan_id_monthly' => 123]);
        $user = $this->makeUserWithCard();

        $service = new class extends EfiBankService {
            protected function fetchCheckoutLink(array $params, array $body): array
            {
                return ['data' => []];
            }
        };

        $this->expectException(RuntimeException::class);
        $service->createCheckoutLink($user, 'monthly');
    }

    // ── handleWebhookToken ───────────────────────────────────────────────────

    public function test_webhook_ativa_pro_quando_status_active(): void
    {
        $user = $this->makeUserWithCard('free');

        $service = new class extends EfiBankService {
            protected function fetchNotification(string $token): array
            {
                return ['data' => [[
                    'type'        => 'subscription',
                    'status'      => ['current' => 'active', 'previous' => 'new'],
                    'identifiers' => ['subscription_id' => 999],
                ]]];
            }

            protected function fetchSubscriptionDetail(int $subscriptionId): array
            {
                return ['data' => [
                    'custom_id' => 'user-' . static::$testUserId,
                    'plan'      => ['plan_id' => 1, 'interval' => 1],
                ]];
            }

            public static int $testUserId = 0;
        };
        $service::$testUserId = $user->id;

        $service->handleWebhookToken('token-abc');

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertSame('999', $user->efi_subscription_id);
    }

    public function test_webhook_ativa_pro_por_um_ano_quando_plano_anual(): void
    {
        $user = $this->makeUserWithCard('free');

        $service = new class extends EfiBankService {
            public static int $testUserId = 0;

            protected function fetchNotification(string $token): array
            {
                return ['data' => [[
                    'type'        => 'subscription_charge',
                    'status'      => ['current' => 'paid', 'previous' => 'waiting'],
                    'identifiers' => ['subscription_id' => 999, 'charge_id' => 1],
                ]]];
            }

            protected function fetchSubscriptionDetail(int $subscriptionId): array
            {
                return ['data' => [
                    'custom_id' => 'user-' . static::$testUserId,
                    'plan'      => ['plan_id' => 2, 'interval' => 12],
                ]];
            }
        };
        $service::$testUserId = $user->id;

        $service->handleWebhookToken('token-abc');

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertTrue($user->plan_expires_at->greaterThan(now()->addMonths(11)));
    }

    public function test_webhook_rebaixa_para_free_e_envia_email_quando_cancelado(): void
    {
        Mail::fake();
        $user = $this->makeUserWithCard('pro');
        $user->update(['plan_expires_at' => now()->addMonth()]);

        $service = new class extends EfiBankService {
            public static int $testUserId = 0;

            protected function fetchNotification(string $token): array
            {
                return ['data' => [[
                    'type'        => 'subscription',
                    'status'      => ['current' => 'canceled', 'previous' => 'active'],
                    'identifiers' => ['subscription_id' => 999],
                ]]];
            }

            protected function fetchSubscriptionDetail(int $subscriptionId): array
            {
                return ['data' => ['custom_id' => 'user-' . static::$testUserId, 'plan' => ['interval' => 1]]];
            }
        };
        $service::$testUserId = $user->id;

        $service->handleWebhookToken('token-abc');

        $user->refresh();
        $this->assertFalse($user->isPro());
        Mail::assertSent(PlanDowngradedMail::class, fn ($mail) => $mail->user->id === $user->id);
    }

    public function test_webhook_ignora_evento_de_usuario_inexistente_sem_erro(): void
    {
        $service = new class extends EfiBankService {
            protected function fetchNotification(string $token): array
            {
                return ['data' => [[
                    'type'        => 'subscription',
                    'status'      => ['current' => 'active', 'previous' => 'new'],
                    'identifiers' => ['subscription_id' => 999],
                ]]];
            }

            protected function fetchSubscriptionDetail(int $subscriptionId): array
            {
                return ['data' => ['custom_id' => 'user-999999', 'plan' => ['interval' => 1]]];
            }
        };

        $service->handleWebhookToken('token-abc');
        $this->assertTrue(true); // não deve lançar exceção
    }

    // ── CheckoutController ───────────────────────────────────────────────────

    public function test_checkout_controller_redireciona_para_url_de_pagamento(): void
    {
        $user = $this->makeUserWithCard();

        $fake = new class extends EfiBankService {
            protected function fetchCheckoutLink(array $params, array $body): array
            {
                return ['data' => ['payment_url' => 'https://pagamento.gerencianet.com.br/xyz']];
            }
        };
        $this->app->instance(EfiBankService::class, $fake);
        config(['services.efibank.plan_id_monthly' => 123]);

        $response = $this->actingAs($user)->get('/dashboard/checkout/monthly');

        $response->assertRedirect('https://pagamento.gerencianet.com.br/xyz');
    }

    public function test_checkout_controller_mostra_erro_amigavel_se_efi_falhar(): void
    {
        $user = $this->makeUserWithCard();
        config(['services.efibank.plan_id_monthly' => null]);

        $response = $this->actingAs($user)->get('/dashboard/checkout/monthly');

        $response->assertRedirect(route('dashboard.plan'));
        $response->assertSessionHas('erro');
    }

    // ── Webhook endpoint ─────────────────────────────────────────────────────

    public function test_webhook_endpoint_exige_token_de_notificacao(): void
    {
        $response = $this->postJson('/webhook/efibank', []);

        $response->assertStatus(400);
    }

    public function test_webhook_endpoint_processa_token_com_sucesso(): void
    {
        $fake = new class extends EfiBankService {
            public bool $called = false;

            public function handleWebhookToken(string $token): void
            {
                $this->called = true;
            }
        };
        $this->app->instance(EfiBankService::class, $fake);

        $response = $this->postJson('/webhook/efibank', ['notification' => 'token-abc']);

        $response->assertOk()->assertJson(['ok' => true]);
    }
}
