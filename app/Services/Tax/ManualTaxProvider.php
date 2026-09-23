<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\Services\TaxCalculator;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

class ManualTaxProvider implements TaxProvider
{
    public function __construct(private readonly TaxCalculator $calculator) {}

    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $countryCode = (string) ($request->address['country_code'] ?? $request->address['country'] ?? '');
        $provinceCode = isset($request->address['province_code'])
            ? (string) $request->address['province_code']
            : null;
        $rate = $this->calculator->rateForAddress($request->rates, $countryCode, $provinceCode, $request->defaultRate);
        $lines = $this->calculator->calculate(
            $request->lineAmounts,
            $request->shippingAmount,
            $rate,
            $request->shippingTaxable,
            $request->pricesIncludeTax,
        );
        $total = array_sum(array_map(static fn ($line): int => $line->amount, $lines));

        return new TaxCalculationResult($lines, $total, [
            'provider' => 'manual',
            'status' => 'calculated',
            'rate' => $rate,
            'tax_lines' => array_map(static fn ($line): array => $line->toArray(), $lines),
            'total_tax_amount' => $total,
        ]);
    }
}
