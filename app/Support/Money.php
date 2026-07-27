<?php

namespace App\Support;

/**
 * Formats monetary amounts stored as integers in minor units (cents) per
 * spec 04 "Currency Formatting": period decimal separator, comma thousands
 * separator, always two decimals, ISO currency code after the amount.
 */
class Money
{
    /**
     * Format an amount in minor units for display, e.g. 2499 -> "24.99 EUR".
     */
    public static function format(int $cents, string $currency): string
    {
        return number_format($cents / 100, 2, '.', ',').' '.strtoupper($currency);
    }
}
