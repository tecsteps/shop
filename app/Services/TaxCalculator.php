<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @param  array{country?: string}  $address
     * @return array{tax_lines: list<TaxLine>, total: int}
     */
    public function calculate(int $amount, TaxSettings $settings, array $address = []): array
    {
        $config = $settings->config_json ?? [];
        $rateBps = $config['default_rate_bps'] ?? 0;

        if ($rateBps === 0) {
            return ['tax_lines' => [], 'total' => 0];
        }

        if ($settings->prices_include_tax) {
            $taxAmount = $this->extractInclusive($amount, $rateBps);
        } else {
            $taxAmount = $this->addExclusive($amount, $rateBps);
        }

        $taxLine = new TaxLine(
            name: 'Tax',
            rate: $rateBps,
            amount: $taxAmount,
        );

        return ['tax_lines' => [$taxLine], 'total' => $taxAmount];
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
