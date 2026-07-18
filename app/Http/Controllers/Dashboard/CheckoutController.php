<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function redirect(Request $request, string $type)
    {
        // Redireciona para o link de pagamento do Efi Bank
        // Os links são configurados no painel Efi Bank e colocados como env vars
        $url = match ($type) {
            'annual'  => config('services.efibank.checkout_url_annual'),
            default   => config('services.efibank.checkout_url_monthly'),
        };

        if (!$url) {
            return redirect()->route('dashboard.plan')
                ->with('erro', 'Link de pagamento não configurado. Entre em contato com o suporte.');
        }

        return redirect()->away($url);
    }
}
