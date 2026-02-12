<?php

namespace App\Support;

class MoneyFormatter
{
    public static function format(int $cents, string $currency = 'EUR'): string
    {
        $amount = number_format($cents / 100, 2, '.', '');

        return $amount.' '.strtoupper($currency);
    }
}
