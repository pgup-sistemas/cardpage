<?php

namespace App\Filament\Widgets;

use App\Models\Card;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaasStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers  = User::count();
        $proUsers    = User::where('plan', 'pro')
            ->where(fn ($q) => $q->whereNull('plan_expires_at')->orWhere('plan_expires_at', '>', now()))
            ->count();
        $trialUsers  = User::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();
        $totalCards  = Card::count();
        $activeCards = Card::where('is_active', true)->count();
        $mrr         = $proUsers * 19.90;
        $conversion  = $totalUsers > 0 ? round(($proUsers / $totalUsers) * 100, 1) : 0;

        return [
            Stat::make('Total de usuários', number_format($totalUsers))
                ->description("+{$trialUsers} em trial")
                ->color('primary'),
            Stat::make('Usuários Pro', number_format($proUsers))
                ->description("Conversão: {$conversion}%")
                ->color('success'),
            Stat::make('MRR', 'R$ ' . number_format($mrr, 2, ',', '.'))
                ->description('Receita mensal recorrente')
                ->color('warning'),
            Stat::make('Cartões ativos', number_format($activeCards) . ' / ' . number_format($totalCards))
                ->color('info'),
        ];
    }
}
