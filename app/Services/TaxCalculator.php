<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * Calculate tax for a given amount using the store's tax settings.
     *
     * @param  array{country_code?: string}  $address
     * @return array{tax_lines: array<int, TaxLine>, tax_total: int}
     */
    public function calculate(int $amount, ?TaxSettings $settings, array $address = []): array
    {
        if (! $settings || $settings->rate_percent === 0) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $settings->rate_percent);
        } else {
            $taxAmount = $this->addExclusive($amount, $settings->rate_percent);
        }

        $taxLine = new TaxLine(
            name: 'Tax '.($settings->rate_percent / 100).'%',
            rate: $settings->rate_percent,
            amount: $taxAmount,
        );

        return ['tax_lines' => [$taxLine], 'tax_total' => $taxAmount];
    }

    /**
     * Extract tax from a gross amount (prices include tax).
     * Formula: tax = gross - (gross * 10000 / (10000 + rate))
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        $netAmount = (int) round($grossAmount * 10000 / (10000 + $rateBasisPoints));

        return $grossAmount - $netAmount;
    }

    /**
     * Add tax to a net amount (prices exclude tax).
     * Formula: tax = net * rate / 10000
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }
}
