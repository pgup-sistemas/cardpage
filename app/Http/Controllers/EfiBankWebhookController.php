<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EfiBankWebhookController extends Controller
{
    public function handle(Request $request, PlanService $planService): JsonResponse
    {
        $payload        = $request->all();
        $type           = $payload['type'] ?? null;
        $subscriptionId = $payload['subscription']['id'] ?? null;

        if (!$subscriptionId) {
            return response()->json(['ok' => false, 'erro' => 'subscription_id ausente'], 400);
        }

        $user = User::where('efi_subscription_id', $subscriptionId)->first();
        if (!$user) {
            return response()->json(['ok' => false, 'erro' => 'usuário não encontrado'], 404);
        }

        match ($type) {
            'subscription_paid'     => $planService->activatePro($user, $subscriptionId),
            'subscription_expired',
            'subscription_canceled' => $planService->downgradeToFree($user),
            default                 => null,
        };

        return response()->json(['ok' => true]);
    }
}
