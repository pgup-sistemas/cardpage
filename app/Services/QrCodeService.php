<?php

namespace App\Services;

use App\Models\Card;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateSvg(Card $card): string
    {
        return $this->svg(url('/u/' . $card->slug));
    }

    public function generatePng(Card $card): string
    {
        return QrCode::format('png')
            ->size(600)
            ->margin(2)
            ->generate(url('/u/' . $card->slug));
    }

    /** Gera SVG para qualquer string (URL, payload PIX, etc.) */
    public function svg(string $content, int $size = 200): string
    {
        return QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->generate($content);
    }

    // ── PIX EMV BR Code ────────────────────────────────────────────────────────

    /**
     * Gera o payload "Pix copia e cola" (padrão EMV BR Code — BCB Resolução nº 1/2020).
     * Não requer integração bancária — funciona com qualquer chave PIX.
     */
    public function pixPayload(
        string $pixKey,
        float  $amount,
        string $merchantName,
        string $city = 'Brasil',
        string $txid = '***'
    ): string {
        $merchantName = substr($this->toAscii($merchantName), 0, 25);
        $city         = substr($this->toAscii($city), 0, 15);
        $txid         = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 25) ?: '***';
        $amountStr    = number_format($amount, 2, '.', '');

        $gui        = $this->emvField('00', 'br.gov.bcb.pix');
        $key        = $this->emvField('01', $pixKey);
        $mai        = $this->emvField('26', $gui . $key);
        $additional = $this->emvField('62', $this->emvField('05', $txid));

        $payload =
            $this->emvField('00', '01')
            . $mai
            . $this->emvField('52', '0000')
            . $this->emvField('53', '986')
            . $this->emvField('54', $amountStr)
            . $this->emvField('58', 'BR')
            . $this->emvField('59', $merchantName)
            . $this->emvField('60', $city)
            . $additional
            . '6304';

        return $payload . $this->crc16($payload);
    }

    private function emvField(string $id, string $value): string
    {
        return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            }
        }
        return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
    }

    private function toAscii(string $text): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        return preg_replace('/[^\x20-\x7E]/', '', $converted ?: $text);
    }
}

