<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;
use App\ValueObjects\TaxResult;

class TaxCalculator
{
    /**
     * @param  array{country?: string}  $address
     */
    public function calculate(int $amount, ?TaxSettings $settings, array $address = []): TaxResult
    {
        if (! $settings || ! $settings->is_active || $settings->rate <= 0) {
            return new TaxResult(taxAmount: 0, taxLines: []);
        }

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $settings->rate);
        } else {
            $taxAmount = $this->addExclusive($amount, $settings->rate);
        }

        $taxLine = new TaxLine(
            name: $settings->tax_name,
            rate: $settings->rate,
            amount: $taxAmount,
        );

        return new TaxResult(
            taxAmount: $taxAmount,
            taxLines: [$taxLine],
        );
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
}
