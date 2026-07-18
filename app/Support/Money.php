<?php

namespace App\Support;

class Money
{
    /**
     * Format an integer minor-unit amount (cents) as "24.99 EUR".
     */
    public static function format(int $amount, string $currency = 'EUR'): string
    {
        $negative = $amount < 0;
        $decimal = number_format(abs($amount) / 100, 2, '.', ',');

        return ($negative ? '-' : '').$decimal.' '.$currency;
    }
}
