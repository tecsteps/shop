<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use RuntimeException;

final class StripeTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $fallback = (array) ($request->taxSettings?->config_json ?? []);
        if (($fallback['fallback'] ?? 'allow') === 'block') {
            throw new RuntimeException('The configured tax provider is unavailable.');
        }

        return new TaxCalculationResult(
            [],
            0,
            array_fill(0, count($request->lineItems), 0),
            0,
            'stripe',
            ['status' => 'unavailable', 'fallback' => 'allow'],
        );
    }
}
