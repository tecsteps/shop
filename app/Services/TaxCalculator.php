<?php

namespace App\Services;

use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @param  list<int>  $lineAmounts
     * @return list<TaxLine>
     */
    public function calculate(array $lineAmounts, int $shippingAmount, int $rate, bool $shippingTaxable = false, bool $pricesIncludeTax = false): array
    {
        if ($rate <= 0) {
            return [];
        }

        $baseAmount = array_sum($lineAmounts) + ($shippingTaxable ? $shippingAmount : 0);
        $taxAmount = $pricesIncludeTax
            ? intdiv(($baseAmount * $rate) + intdiv(10000 + $rate, 2), 10000 + $rate)
            : intdiv(($baseAmount * $rate) + 5000, 10000);

        return $taxAmount > 0 ? [new TaxLine('Sales tax', $rate, $taxAmount)] : [];
    }

    /** @param array<string, mixed> $rates */
    public function rateForAddress(array $rates, string $countryCode, ?string $provinceCode = null, int $defaultRate = 0): int
    {
        $countryCode = strtoupper($countryCode);
        $provinceCode = strtoupper((string) $provinceCode);
        $countryRate = $rates[$countryCode] ?? null;

        if (is_array($countryRate) && $provinceCode !== '' && isset($countryRate[$provinceCode])) {
            return (int) $countryRate[$provinceCode];
        }

        if (is_int($countryRate) || is_numeric($countryRate)) {
            return (int) $countryRate;
        }

        return $defaultRate;
    }
}
