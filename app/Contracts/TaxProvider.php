<?php

namespace App\Contracts;

use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxResult;

interface TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxResult;
}
