<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Stub for the Stripe Tax API integration (spec 05 §8.4).
 *
 * A real implementation sends line items and the shipping address to the
 * Stripe Tax API and stores the full response in
 * checkouts.tax_provider_snapshot_json. Fallback behaviour is configurable
 * per store via tax_settings.config_json['fallback']:
 *
 *  - "block": block checkout progression when the Stripe Tax API errors.
 *  - "allow": allow checkout to proceed without tax (and log a warning).
 *
 * Until the API integration exists, this stub always returns a zero-tax
 * result, which corresponds to the "allow" fallback path.
 */
class StripeTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        return TaxCalculationResult::zero();
    }
}
