<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;
use App\ValueObjects\TaxResult;

class TaxCalculator
{
    public function calculate(int $amount, TaxSettings $settings, array $address): TaxResult
    {
        $rates = $settings->rates_json ?? [];
        $rate = (int) ($rates[strtoupper((string) ($address['country_code'] ?? ''))] ?? $settings->default_rate_basis_points);
        $tax = $settings->prices_include_tax || $settings->mode === 'inclusive' ? $this->extractInclusive($amount, $rate) : $this->addExclusive($amount, $rate);

        return new TaxResult($tax, $tax > 0 ? [new TaxLine('Sales tax', $rate, $tax)] : []);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        return $rateBasisPoints > 0 ? intdiv($grossAmount * $rateBasisPoints, 10000 + $rateBasisPoints) : 0;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return intdiv($netAmount * $rateBasisPoints, 10000);
    }
}
