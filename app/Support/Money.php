<?php

namespace App\Support;

class Money
{
    public static function format(int $amount, string $currency = 'EUR'): string
    {
        return number_format($amount / 100, 2, '.', ',').' '.$currency;
    }

    public static function fromDecimalString(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * 100);
    }
}
