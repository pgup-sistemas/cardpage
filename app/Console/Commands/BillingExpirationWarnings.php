<?php

namespace App\Console\Commands;

use App\Mail\PlanDowngradedMail;
use App\Mail\PlanExpiredMail;
use App\Mail\PlanExpiringMail;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class BillingExpirationWarnings extends Command
{
    protected $signature   = 'billing:expiration-warnings';
    protected $description = 'Envia avisos de vencimento do plano Pro e faz downgrade quando expirado';

    public function handle(PlanService $planService): void
    {
        // Aviso 7 dias antes
        User::where('plan', 'pro')
            ->whereNotNull('plan_expires_at')
            ->whereBetween('plan_expires_at', [now()->startOfDay()->addDays(7), now()->endOfDay()->addDays(7)])
            ->each(fn (User $u) => Mail::to($u->email)->send(new PlanExpiringMail($u, 7)));

        // Aviso 1 dia antes
        User::where('plan', 'pro')
            ->whereNotNull('plan_expires_at')
            ->whereBetween('plan_expires_at', [now()->startOfDay()->addDay(), now()->endOfDay()->addDay()])
            ->each(fn (User $u) => Mail::to($u->email)->send(new PlanExpiringMail($u, 1)));

        // Planos expirados — downgrade
        User::where('plan', 'pro')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->each(function (User $u) use ($planService) {
                $planService->downgradeToFree($u);
                Mail::to($u->email)->send(new PlanExpiredMail($u));
            });

        $this->info('Avisos de vencimento enviados com sucesso.');
    }
}
