<?php

namespace App\Support;

/**
 * Minor-units money formatting per spec 04 currency rules.
 */
class Money
{
    public static function format(int $cents, string $currency): string
    {
        return number_format($cents / 100, 2, '.', ',').' '.$currency;
    }
}
