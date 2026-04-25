<?php

namespace App\Services\Shop;

use NumberFormatter;

class Money
{
    public static function format(int $amount, string $currency = 'EUR'): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absolute = abs($amount);

        return $sign.number_format($absolute / 100, 2, '.', ',').' '.$currency;
    }

    public static function bps(int $amount, int $basisPoints): int
    {
        return (int) round($amount * $basisPoints / 10000);
    }
}

