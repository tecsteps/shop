<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * Calculate tax for an amount using the given tax settings.
     *
     * @param  array{country?: string, province_code?: string}  $address
     * @return array{tax_lines: list<TaxLine>, tax_total: int}
     */
    public function calculate(int $amount, ?TaxSettings $settings, array $address = []): array
    {
        if (! $settings) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        $rate = $this->resolveRate($settings, $address);

        if ($rate <= 0) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        if ($settings->prices_include_tax) {
            $tax = $this->extractInclusive($amount, $rate);
        } else {
            $tax = $this->addExclusive($amount, $rate);
        }

        $taxLine = new TaxLine(
            name: 'Tax',
            rate: $rate,
            amount: $tax,
        );

        return ['tax_lines' => [$taxLine], 'tax_total' => $tax];
    }

    /**
     * Extract tax from a gross (tax-inclusive) amount.
     * Uses integer division per spec.
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0 || $grossAmount <= 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    /**
     * Calculate tax to add to a net (tax-exclusive) amount.
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0 || $netAmount <= 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }

    /**
     * Resolve the applicable tax rate in basis points.
     *
     * @param  array{country?: string, province_code?: string}  $address
     */
    private function resolveRate(TaxSettings $settings, array $address): int
    {
        $config = $settings->config_json ?? [];

        return $config['default_rate'] ?? 0;
    }
}
