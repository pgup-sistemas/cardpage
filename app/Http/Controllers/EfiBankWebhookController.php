<?php

namespace App\Http\Controllers;

use App\Services\EfiBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EfiBankWebhookController extends Controller
{
    /**
     * A Efi não envia o evento em si — só um token de notificação. É preciso
     * consultar de volta a API (GET /v1/notification/{token}) para obter os
     * dados reais. Isso já funciona como validação: só quem tem as credenciais
     * corretas (client + certificado) consegue resolver o token em algo útil.
     */
    public function handle(Request $request, EfiBankService $efiBank): JsonResponse
    {
        $token = $request->input('notification');

        if (!$token) {
            return response()->json(['ok' => false, 'erro' => 'token de notificação ausente'], 400);
        }

        try {
            $efiBank->handleWebhookToken($token);
        } catch (Throwable $e) {
            Log::error('efibank.webhook_failed', [
                'token'   => $token,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
