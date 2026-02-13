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
        $config = $settings->config_json ?? [];
        $rate = (int) ($config['default_rate'] ?? 0);

        if ($rate === 0 || $amount === 0) {
            return new TaxLine(
                name: 'Tax',
                rate: $rate,
                amount: 0,
            );
        }

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $rate);
        } else {
            $taxAmount = $this->addExclusive($amount, $rate);
        }

        return new TaxLine(
            name: 'Tax',
            rate: $rate,
            amount: $taxAmount,
        );
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
        $tax = $grossAmount - $net;

        return $tax;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        return intdiv($netAmount * $rateBasisPoints, 10000);
    }
}
