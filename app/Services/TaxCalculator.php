<?php

namespace App\Services;

use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0 || $netAmount === 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0 || $grossAmount === 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{tax_total: int, tax_lines: array<int, TaxLine>}
     */
    public function calculate(int $amount, TaxSettings $settings, array $address): array
    {
        $config = $settings->config_json ?? [];
        $rate = (int) ($config['rate_basis_points'] ?? 0);
        $name = (string) ($config['name'] ?? 'Tax');

        if ($rate === 0 || $amount === 0) {
            return ['tax_total' => 0, 'tax_lines' => []];
        }

        $taxAmount = $this->addExclusive($amount, $rate);

        return [
            'tax_total' => $taxAmount,
            'tax_lines' => [new TaxLine($name, $rate, $taxAmount)],
        ];
    }
}
