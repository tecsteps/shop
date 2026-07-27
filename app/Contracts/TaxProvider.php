<?php

namespace App\Contracts;

use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Tax provider contract (spec 05 §8.1): accepts a calculation request with
 * discounted line items, shipping amount, address and store tax settings,
 * and returns calculated tax lines plus a total.
 */
interface TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult;
}
