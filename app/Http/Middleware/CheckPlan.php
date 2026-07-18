<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlan
{
    public function handle(Request $request, Closure $next, string $feature = 'pro'): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = match ($feature) {
            'pro'      => $user->isPro() || $user->isOnTrial(),
            'agenda'   => $user->isPro() || $user->isOnTrial(),
            'messages' => $user->isPro() || $user->isOnTrial(),
            default    => false,
        };

        if (! $allowed) {
            return redirect()->route('dashboard.plan')
                ->with('aviso', 'Esta funcionalidade requer o plano Pro.');
        }

        return $next($request);
    }
}
