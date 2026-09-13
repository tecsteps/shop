<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

/**
 * Manual tax rate calculation per spec 05 section 8.2.
 */
class ManualTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $lines = [];
        $total = 0;

        foreach ($request->lineItems as $item) {
            $amount = $request->pricesIncludeTax
                ? $this->extractInclusive((int) $item['amount'], $request->rate)
                : $this->addExclusive((int) $item['amount'], $request->rate);

            $lines[] = new TaxLine($request->rateName, $request->rate, $amount);
            $total += $amount;
        }

        if ($request->shippingTaxed && $request->shippingAmount > 0) {
            $shippingTax = $request->pricesIncludeTax
                ? $this->extractInclusive($request->shippingAmount, $request->rate)
                : $this->addExclusive($request->shippingAmount, $request->rate);

            $lines[] = new TaxLine($request->rateName, $request->rate, $shippingTax);
            $total += $shippingTax;
        }

        return new TaxCalculationResult($lines, $total);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($netAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return intdiv($netAmount * $rateBasisPoints, 10000);
    }
}
