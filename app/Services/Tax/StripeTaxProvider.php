<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxResult;

class StripeTaxProvider implements TaxProvider
{
    public function __construct(private readonly ManualTaxProvider $fallback) {}

    public function calculate(TaxCalculationRequest $request): TaxResult
    {
        if (($request->settings->provider_config_json['fallback'] ?? 'allow') === 'block') {
            throw new \RuntimeException('Stripe Tax is not configured for this environment.');
        }

        return new TaxResult(0, []);
    }
}
