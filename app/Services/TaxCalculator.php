<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @return array{tax_lines: TaxLine[], tax_total: int}
     */
    public function calculate(int $amount, ?TaxSettings $settings, array $address = []): array
    {
        if (! $settings) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        $rateBps = $settings->config_json['tax_rate_basis_points'] ?? 0;

        if ($rateBps === 0) {
            return ['tax_lines' => [], 'tax_total' => 0];
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

        return ['tax_lines' => [$taxLine], 'tax_total' => $taxAmount];
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        $netAmount = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $netAmount;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }
}
