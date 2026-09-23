<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\Exceptions\TaxProviderUnavailableException;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * The application deliberately has no Stripe network integration. This provider
 * exposes the documented failure path so stores can choose their fallback policy.
 */
class StripeTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        throw new TaxProviderUnavailableException('Stripe Tax is not connected in this self-contained shop.');
    }
}
