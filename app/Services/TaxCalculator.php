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

        $taxAmount = array_sum(array_map(
            fn (int $lineAmount): int => $this->taxForAmount($lineAmount, $rate, $pricesIncludeTax),
            $lineAmounts,
        ));

        if ($shippingTaxable) {
            $taxAmount += $this->taxForAmount($shippingAmount, $rate, $pricesIncludeTax);
        }

        return $taxAmount > 0 ? [new TaxLine('Sales tax', $rate, $taxAmount)] : [];
    }

    private function taxForAmount(int $amount, int $rate, bool $pricesIncludeTax): int
    {
        if ($amount <= 0) {
            return 0;
        }

        return $pricesIncludeTax
            ? intdiv($amount * $rate, 10000 + $rate)
            : intdiv(($amount * $rate) + 5000, 10000);
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
