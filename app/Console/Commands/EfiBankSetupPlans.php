<?php

namespace App\Console\Commands;

use App\Services\EfiBankService;
use Illuminate\Console\Command;
use Throwable;

class EfiBankSetupPlans extends Command
{
    protected $signature = 'efibank:setup-plans';

    protected $description = 'Cria os planos de assinatura Pro (mensal e anual) na Efi Bank — rodar uma única vez';

    public function handle(EfiBankService $efiBank): int
    {
        if (!config('services.efibank.client_id') || !config('services.efibank.client_secret')) {
            $this->error('EFI_CLIENT_ID / EFI_CLIENT_SECRET não configurados no .env.');
            return self::FAILURE;
        }

        try {
            $this->info('Criando plano mensal...');
            $monthly = $efiBank->createPlan('NEXOSN Pro — Mensal', intervalMonths: 1);
            $monthlyId = $monthly['data']['plan_id'] ?? null;
            $this->line('  plan_id: ' . ($monthlyId ?? '(não retornado — veja a resposta completa abaixo)'));

            $this->info('Criando plano anual...');
            $annual = $efiBank->createPlan('NEXOSN Pro — Anual', intervalMonths: 12);
            $annualId = $annual['data']['plan_id'] ?? null;
            $this->line('  plan_id: ' . ($annualId ?? '(não retornado — veja a resposta completa abaixo)'));

            $this->newLine();
            $this->info('Adicione ao .env:');
            $this->line("EFI_PLAN_ID_MONTHLY={$monthlyId}");
            $this->line("EFI_PLAN_ID_ANNUAL={$annualId}");

            if (!$monthlyId || !$annualId) {
                $this->newLine();
                $this->warn('Resposta completa da API (para localizar o plan_id manualmente):');
                $this->line(json_encode(['monthly' => $monthly, 'annual' => $annual], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } catch (Throwable $e) {
            $this->error('Falha ao criar planos: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
