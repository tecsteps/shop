<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

final class ManualTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        if ($request->taxSettings === null) {
            return new TaxCalculationResult([], 0, array_fill(0, count($request->lineItems), 0));
        }

        $config = (array) $request->taxSettings->config_json;
        $rate = (int) ($config['rate_bps'] ?? $config['default_rate_bps'] ?? $config['rate'] ?? 0);
        if ($rate < 1) {
            return new TaxCalculationResult([], 0, array_fill(0, count($request->lineItems), 0));
        }

        $inclusive = (bool) $request->taxSettings->prices_include_tax;
        $lineAmounts = [];
        foreach ($request->lineItems as $amount) {
            $lineAmounts[] = $inclusive
                ? $amount - intdiv($amount * 10000, 10000 + $rate)
                : (int) round($amount * $rate / 10000);
        }
        $shippingTax = 0;
        if ($request->shippingAmount > 0) {
            $shippingTax = $inclusive
                ? $request->shippingAmount - intdiv($request->shippingAmount * 10000, 10000 + $rate)
                : (int) round($request->shippingAmount * $rate / 10000);
        }
        $tax = array_sum($lineAmounts) + $shippingTax;

        return new TaxCalculationResult(
            [new TaxLine((string) ($config['label'] ?? 'Tax'), $rate, $tax)],
            $tax,
            $lineAmounts,
            $shippingTax,
            'manual',
            ['rate' => $rate, 'prices_include_tax' => $inclusive],
        );
    }
}
