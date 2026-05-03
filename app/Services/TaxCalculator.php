<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function calculate(int $amount, TaxSettings $settings, array $address): TaxLine
    {
        $rate = $this->rateFor($settings, $address);
        $tax = $settings->prices_include_tax
            ? $this->extractInclusive($amount, $rate)
            : $this->addExclusive($amount, $rate) - $amount;

        return new TaxLine('Tax', $rate, max(0, $tax));
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount < 1 || $rateBasisPoints < 1) {
            return 0;
        }

        $netAmount = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $netAmount;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($netAmount < 1 || $rateBasisPoints < 1) {
            return $netAmount;
        }

        return $netAmount + (int) round($netAmount * $rateBasisPoints / 10000);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function rateFor(TaxSettings $settings, array $address): int
    {
        $config = $settings->config_json ?? [];
        $country = $address['country_code'] ?? $address['country'] ?? null;
        $countryRates = $config['country_rates'] ?? [];

        if (is_string($country) && isset($countryRates[$country])) {
            return (int) $countryRates[$country];
        }

        return (int) ($config['default_rate_basis_points'] ?? 0);
    }
}
