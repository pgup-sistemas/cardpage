<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function index(QrCodeService $qr)
    {
        $user = auth()->user();
        $card = $user->card;

        if (!$card) {
            return redirect()->route('dashboard.card')->with('aviso', 'Crie seu cartão primeiro.');
        }

        $qrSvg = $qr->generateSvg($card);
        $cardUrl = url('/u/' . $card->slug);

        return view('dashboard.share', compact('card', 'qrSvg', 'cardUrl'));
    }
}
