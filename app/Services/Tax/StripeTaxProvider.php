<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Stub for a future Stripe Tax API integration.
 *
 * Until the integration lands this falls back to the manually configured rate,
 * so checkout remains functional. A real implementation would call the Stripe
 * Tax API and store the response in `checkouts.tax_provider_snapshot_json`,
 * honouring the configured `block`/`allow` fallback behaviour on error.
 */
class StripeTaxProvider implements TaxProvider
{
    public function __construct(private readonly ManualTaxProvider $fallback) {}

    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        return $this->fallback->calculate($request);
    }
}
