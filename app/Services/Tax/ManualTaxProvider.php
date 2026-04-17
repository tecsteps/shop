<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

class ManualTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $config = $request->taxSettings->config_json ?? [];
        $rateBasisPoints = $config['default_rate'] ?? 0;
        $taxName = $config['tax_name'] ?? 'Tax';
        $pricesIncludeTax = $request->taxSettings->prices_include_tax;

        if ($rateBasisPoints === 0) {
            return new TaxCalculationResult(taxLines: [], totalAmount: 0);
        }

        $totalTax = 0;

        foreach ($request->lineItems as $lineItem) {
            $amount = $lineItem['amount'];

            if ($pricesIncludeTax) {
                $netAmount = intdiv($amount * 10000, 10000 + $rateBasisPoints);
                $lineTax = $amount - $netAmount;
            } else {
                $lineTax = (int) round($amount * $rateBasisPoints / 10000);
            }

            $totalTax += $lineTax;
        }

        $shippingTax = 0;
        if ($request->shippingAmount > 0) {
            if ($pricesIncludeTax) {
                $netShipping = intdiv($request->shippingAmount * 10000, 10000 + $rateBasisPoints);
                $shippingTax = $request->shippingAmount - $netShipping;
            } else {
                $shippingTax = (int) round($request->shippingAmount * $rateBasisPoints / 10000);
            }
            $totalTax += $shippingTax;
        }

        $taxLines = [
            new TaxLine(
                name: $taxName,
                rate: $rateBasisPoints,
                amount: $totalTax,
            ),
        ];

        return new TaxCalculationResult(taxLines: $taxLines, totalAmount: $totalTax);
    }
}
