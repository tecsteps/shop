<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;
use App\ValueObjects\TaxResult;

class TaxCalculator
{
    /**
     * Calculate tax for an amount per the store's tax settings. All math is
     * integer-based; rates are basis points (1900 = 19.00%).
     *
     * @param  array<string, mixed>  $address
     */
    public function calculate(int $amount, ?TaxSettings $settings, array $address = []): TaxResult
    {
        if ($settings === null || $amount <= 0) {
            return TaxResult::zero();
        }

        $rate = $settings->defaultRateBasisPoints();

        if ($rate <= 0) {
            return TaxResult::zero();
        }

        $tax = $settings->prices_include_tax
            ? $this->extractInclusive($amount, $rate)
            : $this->addExclusive($amount, $rate);

        return new TaxResult([new TaxLine($settings->taxName(), $rate, $tax)], $tax);
    }

    /**
     * Extract the tax portion from a gross (tax-inclusive) amount using
     * deterministic integer division: net = intdiv(gross * 10000, 10000 + rate).
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    /**
     * Tax to add on top of a net (tax-exclusive) amount. Uses integer
     * division (truncation) for determinism, matching the spec's expected
     * values (e.g. 5499 at 19% = 1044, 8999 at 7% = 629).
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($netAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return intdiv($netAmount * $rateBasisPoints, 10000);
    }
}
