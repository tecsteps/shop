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
            return new TaxCalculationResult([], 0);
        }

        $config = (array) $request->taxSettings->config_json;
        $rate = (int) ($config['rate_bps'] ?? $config['default_rate_bps'] ?? $config['rate'] ?? 0);
        if ($rate < 1) {
            return new TaxCalculationResult([], 0);
        }

        $inclusive = (bool) $request->taxSettings->prices_include_tax;
        $tax = 0;
        foreach ($request->lineItems as $amount) {
            $tax += $inclusive
                ? $amount - intdiv($amount * 10000, 10000 + $rate)
                : (int) round($amount * $rate / 10000);
        }
        if ($request->shippingAmount > 0) {
            $tax += $inclusive
                ? $request->shippingAmount - intdiv($request->shippingAmount * 10000, 10000 + $rate)
                : (int) round($request->shippingAmount * $rate / 10000);
        }

        return new TaxCalculationResult([new TaxLine((string) ($config['label'] ?? 'Tax'), $rate, $tax)], $tax);
    }
}
