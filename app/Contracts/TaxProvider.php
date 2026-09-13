<?php

namespace App\Contracts;

use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

interface TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult;
}
