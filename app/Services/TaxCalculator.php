<?php

namespace App\Services;

use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * Calculate tax for an amount given tax settings and shipping address.
     *
     * @param  object{prices_include_tax: bool, config_json: array<string, mixed>}  $settings
     * @param  array{country?: string, province_code?: string}  $address
     * @return array{tax_lines: array<TaxLine>, tax_total: int}
     */
    public function calculate(int $amount, object $settings, array $address): array
    {
        $config = $settings->config_json ?? [];

        $rate = $this->resolveRate($config, $address);

        if ($rate <= 0) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        $name = $this->resolveTaxName($config, $address);

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $rate);
        } else {
            $taxAmount = $this->addExclusive($amount, $rate);
        }

        $taxLine = new TaxLine(
            name: $name,
            rate: $rate,
            amount: $taxAmount,
        );

        return [
            'tax_lines' => [$taxLine],
            'tax_total' => $taxAmount,
        ];
    }

    /**
     * Extract tax from a gross (tax-inclusive) amount.
     * Uses integer division for deterministic results.
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0) {
            return 0;
        }

        $netAmount = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $netAmount;
    }

    /**
     * Add tax to a net (tax-exclusive) amount.
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }

    /**
     * Resolve the tax rate in basis points from config and address.
     *
     * @param  array<string, mixed>  $config
     * @param  array{country?: string, province_code?: string}  $address
     */
    private function resolveRate(array $config, array $address): int
    {
        $rates = $config['rates'] ?? [];
        $countryCode = $address['country'] ?? '';
        $provinceCode = $address['province_code'] ?? '';

        foreach ($rates as $rateEntry) {
            $rateCountries = $rateEntry['countries'] ?? [];
            $rateRegions = $rateEntry['regions'] ?? [];

            if (in_array($countryCode, $rateCountries, true)) {
                if (! empty($rateRegions) && ! empty($provinceCode)) {
                    if (in_array($provinceCode, $rateRegions, true)) {
                        return $rateEntry['rate'] ?? 0;
                    }

                    continue;
                }

                return $rateEntry['rate'] ?? 0;
            }
        }

        return $config['default_rate'] ?? 0;
    }

    /**
     * Resolve the tax name from config and address.
     *
     * @param  array<string, mixed>  $config
     * @param  array{country?: string, province_code?: string}  $address
     */
    private function resolveTaxName(array $config, array $address): string
    {
        $rates = $config['rates'] ?? [];
        $countryCode = $address['country'] ?? '';

        foreach ($rates as $rateEntry) {
            $rateCountries = $rateEntry['countries'] ?? [];
            if (in_array($countryCode, $rateCountries, true)) {
                return $rateEntry['name'] ?? 'Tax';
            }
        }

        return $config['default_name'] ?? 'Tax';
    }
}
