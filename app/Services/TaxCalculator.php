<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * Calculate tax for a given taxable amount.
     * Returns an array of TaxLine value objects and the total tax amount.
     *
     * @param  array{country_code?: ?string, province_code?: ?string}  $address
     * @return array{lines: array<int, TaxLine>, total: int}
     */
    public function calculate(int $taxableAmount, TaxSettings $settings, array $address = []): array
    {
        if ($taxableAmount <= 0) {
            return ['lines' => [], 'total' => 0];
        }

        $rate = $this->resolveRate($settings, $address);
        if ($rate <= 0) {
            return ['lines' => [], 'total' => 0];
        }

        if ($settings->prices_include_tax) {
            $tax = $this->extractInclusive($taxableAmount, $rate);
        } else {
            $tax = $this->addExclusive($taxableAmount, $rate);
        }

        $line = new TaxLine(
            name: $this->resolveRateName($settings, $address),
            rate: $rate,
            amount: $tax,
        );

        return ['lines' => [$line], 'total' => $tax];
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0 || $grossAmount <= 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0 || $netAmount <= 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }

    protected function resolveRate(TaxSettings $settings, array $address): int
    {
        $config = $settings->config_json ?? [];

        $regionRates = $config['region_rates'] ?? [];
        $region = $address['province_code'] ?? null;
        if ($region && isset($regionRates[$region])) {
            return (int) $regionRates[$region];
        }

        $countryRates = $config['country_rates'] ?? [];
        $country = $address['country_code'] ?? null;
        if ($country && isset($countryRates[$country])) {
            return (int) $countryRates[$country];
        }

        return (int) ($config['default_rate_bps'] ?? 0);
    }

    protected function resolveRateName(TaxSettings $settings, array $address): string
    {
        $config = $settings->config_json ?? [];

        return (string) ($config['rate_name'] ?? 'Tax');
    }
}
