<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;
use App\ValueObjects\TaxResult;

class TaxCalculator
{
    /** @param array<string, mixed> $address */
    public function calculate(int $amount, TaxSettings $settings, array $address = []): TaxResult
    {
        $rate = (int) ($settings->config_json['rates'][$address['country_code'] ?? $address['country'] ?? '']
            ?? $settings->config_json['default_rate_bps']
            ?? $settings->config_json['rate_bps']
            ?? 0);

        if ($settings->prices_include_tax) {
            $tax = $this->extractInclusive($amount, $rate);

            return new TaxResult($amount - $tax, $tax, $amount, [new TaxLine('Tax', $rate, $tax)]);
        }

        $tax = $this->addExclusive($amount, $rate);

        return new TaxResult($amount, $tax, $amount + $tax, [new TaxLine('Tax', $rate, $tax)]);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return (int) round($netAmount * $rateBasisPoints / 10000);
    }
}
