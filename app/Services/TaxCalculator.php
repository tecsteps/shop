<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @param  array<string, mixed>  $address
     * @return array{taxLines: array<TaxLine>, taxTotal: int}
     */
    public function calculate(int $amount, TaxSettings $settings, array $address): array
    {
        if ($settings->rate_basis_points <= 0) {
            return ['taxLines' => [], 'taxTotal' => 0];
        }

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $settings->rate_basis_points);
        } else {
            $taxAmount = $this->addExclusive($amount, $settings->rate_basis_points);
        }

        $taxLine = new TaxLine(
            name: $settings->tax_name,
            rate: $settings->rate_basis_points,
            amount: $taxAmount,
        );

        return ['taxLines' => [$taxLine], 'taxTotal' => $taxAmount];
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        return (int) floor($grossAmount * $rateBasisPoints / (10000 + $rateBasisPoints));
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return (int) floor($netAmount * $rateBasisPoints / 10000);
    }
}
