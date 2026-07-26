<?php

namespace App\Services;

use App\Contracts\TaxProvider;
use App\Enums\TaxMode;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Orchestrates tax provider selection based on the store's tax settings and
 * delegates the calculation (spec 05 §8.1).
 */
class TaxCalculator
{
    public function __construct(
        private ManualTaxProvider $manualProvider,
        private StripeTaxProvider $stripeProvider,
    ) {}

    /**
     * Calculate tax for the given request via the configured provider.
     */
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        return $this->resolveProvider($request)->calculate($request);
    }

    /**
     * Tax added on top of a net amount (integer math, per-line truncation).
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return $this->manualProvider->addExclusive($netAmount, $rateBasisPoints);
    }

    /**
     * Tax portion contained in a gross (tax-inclusive) amount.
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        return $this->manualProvider->extractInclusive($grossAmount, $rateBasisPoints);
    }

    /**
     * Pick the provider implementation for the request's tax settings.
     */
    private function resolveProvider(TaxCalculationRequest $request): TaxProvider
    {
        $settings = $request->taxSettings;

        if ($settings->mode === TaxMode::Provider && $settings->provider === 'stripe_tax') {
            return $this->stripeProvider;
        }

        return $this->manualProvider;
    }
}
