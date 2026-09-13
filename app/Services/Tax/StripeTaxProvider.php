<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Tax API integration stub per spec 05 section 8.4.
 * Falls back per store config: "block" throws, "allow" proceeds without tax.
 */
class StripeTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $fallback = $request->config['stripe_fallback'] ?? 'allow';

        if ($fallback === 'block') {
            throw new \RuntimeException('Stripe Tax provider is not configured and fallback is set to block checkout.');
        }

        Log::warning('Stripe Tax provider stub used; proceeding without tax.', [
            'line_count' => count($request->lineItems),
        ]);

        return new TaxCalculationResult([], 0);
    }
}
