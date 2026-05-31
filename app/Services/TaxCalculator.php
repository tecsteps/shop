<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Tax calculation orchestrator.
 *
 * Provides the two integer-math primitives used throughout pricing
 * ({@see self::addExclusive()} and {@see self::extractInclusive()}) and delegates
 * full per-line calculation to the configured {@see \App\Contracts\TaxProvider}.
 *
 * All amounts are integers in minor units (cents); rates are integer basis
 * points (1900 = 19.00%).
 */
class TaxCalculator
{
    public function __construct(
        private readonly ManualTaxProvider $manualProvider,
        private readonly StripeTaxProvider $stripeProvider,
    ) {}

    /**
     * Tax added on top of a net (tax-exclusive) amount.
     *
     * Uses integer division (truncation) for deterministic results across
     * lines: `intdiv(net * rate, 10000)`.
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0 || $netAmount <= 0) {
            return 0;
        }

        return intdiv($netAmount * $rateBasisPoints, 10000);
    }

    /**
     * Tax extracted from a gross (tax-inclusive) amount.
     *
     * Uses integer division (truncation) for deterministic extraction:
     * `net = intdiv(gross * 10000, 10000 + rate)`, `tax = gross - net`.
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0 || $grossAmount <= 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    /**
     * Resolve and delegate to the appropriate tax provider for the store.
     */
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        return $this->providerFor($request->taxSettings)->calculate($request);
    }

    private function providerFor(TaxSettings $settings): ManualTaxProvider|StripeTaxProvider
    {
        return $settings->mode === TaxMode::Provider && $settings->provider === 'stripe_tax'
            ? $this->stripeProvider
            : $this->manualProvider;
    }
}
