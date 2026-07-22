<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\EfiBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CheckoutController extends Controller
{
    public function redirect(string $type, EfiBankService $efiBank): RedirectResponse
    {
        $planType = $type === 'annual' ? 'annual' : 'monthly';

        try {
            $url = $efiBank->createCheckoutLink(auth()->user(), $planType);
        } catch (RuntimeException $e) {
            Log::error('checkout.efibank_error', ['message' => $e->getMessage()]);

            return redirect()->route('dashboard.plan')
                ->with('erro', 'Não foi possível gerar o link de pagamento agora. Tente novamente em instantes ou contate o suporte.');
        }

        return redirect()->away($url);
    }
}
