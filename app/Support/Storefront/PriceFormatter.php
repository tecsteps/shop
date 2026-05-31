<?php

namespace App\Support\Storefront;

/**
 * Formats integer minor-unit (cents) amounts for storefront display.
 *
 * The storefront spec mandates a specific presentation that differs from the
 * locale-driven {@see \Illuminate\Support\Number::currency()} output:
 *   - period decimal separator, comma thousands separator
 *   - the ISO currency code after the amount with a single space ("24.99 EUR")
 *   - always two decimal places; "0.00 EUR" for free amounts
 *   - negative (refund) amounts prefixed with "-" ("-12.50 EUR")
 *
 * Keeping this in one helper guarantees consistent formatting across every
 * storefront price (the {@see resources/views/storefront/components/price.blade.php}
 * component, cart, checkout, and order pages).
 */
class PriceFormatter
{
    /**
     * Format an integer minor-unit amount as a display string, e.g. "1,499.00 EUR".
     */
    public static function format(int $amountInCents, string $currency = 'USD'): string
    {
        $sign = $amountInCents < 0 ? '-' : '';
        $absolute = abs($amountInCents);

        $major = intdiv($absolute, 100);
        $minor = $absolute % 100;

        $formatted = number_format($major).'.'.str_pad((string) $minor, 2, '0', STR_PAD_LEFT);

        return $sign.$formatted.' '.strtoupper($currency);
    }
}
