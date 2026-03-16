<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

class StripeTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $config = $request->taxSettings->config_json ?? [];
        $fallback = $config['fallback'] ?? 'allow';

        if ($fallback === 'block') {
            throw new \RuntimeException('Stripe Tax API is not yet implemented. Checkout blocked by fallback policy.');
        }

        return new TaxCalculationResult(
            taxLines: [new TaxLine(name: 'Tax (stub)', rate: 0, amount: 0)],
            totalAmount: 0,
        );
    }
}
