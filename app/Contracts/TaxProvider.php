<?php

namespace App\Contracts;

use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Calculates tax for a checkout. Implementations select rates from manual
 * configuration ({@see \App\Services\Tax\ManualTaxProvider}) or an external
 * provider ({@see \App\Services\Tax\StripeTaxProvider}).
 */
interface TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult;
}
