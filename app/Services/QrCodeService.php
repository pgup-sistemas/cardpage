<?php

namespace App\Services;

use App\Models\Card;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateSvg(Card $card): string
    {
        $url = url('/u/' . $card->slug);

        return QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->color(0, 48, 73)    // #003049
            ->generate($url);
    }

    public function generatePng(Card $card): string
    {
        $url = url('/u/' . $card->slug);

        return QrCode::format('png')
            ->size(600)
            ->margin(2)
            ->generate($url);
    }
}
