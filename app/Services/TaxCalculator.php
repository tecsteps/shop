<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @return array{tax_lines: array<TaxLine>, tax_total: int}
     */
    public function calculate(int $amount, TaxSettings $settings, array $address): array
    {
        if ($settings->mode === TaxMode::Provider) {
            return $this->calculateViaProvider($amount, $settings, $address);
        }

        return $this->calculateManual($amount, $settings);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        $netAmount = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $netAmount;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return (int) round($netAmount * $rateBasisPoints / 10000);
    }

    /**
     * @return array{tax_lines: array<TaxLine>, tax_total: int}
     */
    protected function calculateManual(int $amount, TaxSettings $settings): array
    {
        $config = $settings->config_json ?? [];
        $rateBasisPoints = $config['default_rate_basis_points'] ?? 0;
        $taxName = $config['tax_name'] ?? 'Tax';

        if ($rateBasisPoints <= 0) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $rateBasisPoints);
        } else {
            $taxAmount = $this->addExclusive($amount, $rateBasisPoints);
        }

        $taxLine = new TaxLine(
            name: $taxName,
            rate: $rateBasisPoints,
            amount: $taxAmount,
        );

        return [
            'tax_lines' => [$taxLine],
            'tax_total' => $taxAmount,
        ];
    }

    /**
     * @return array{tax_lines: array<TaxLine>, tax_total: int}
     */
    protected function calculateViaProvider(int $amount, TaxSettings $settings, array $address): array
    {
        // Stub for Stripe Tax provider - falls back to manual calculation
        return $this->calculateManual($amount, $settings);
    }
}
