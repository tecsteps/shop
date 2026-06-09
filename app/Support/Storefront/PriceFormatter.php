<?php

namespace App\Support\Storefront;

/**
 * Formats integer minor-unit amounts for storefront display following the
 * spec: period decimal separator, comma thousands separator, two decimal
 * places, currency code after the amount (e.g. "1,499.00 EUR"), and a "-"
 * prefix for negative amounts.
 */
class PriceFormatter
{
    public static function format(int $amount, string $currency = 'EUR'): string
    {
        $sign = $amount < 0 ? '-' : '';

        $formatted = number_format(abs($amount) / 100, 2, '.', ',');

        return $sign.$formatted.' '.strtoupper($currency);
    }
}
