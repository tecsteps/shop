<?php

namespace App\Services;

use App\Contracts\TaxProvider;
use App\Models\TaxSettings;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxResult;

class TaxCalculator
{
    public function __construct(private readonly ?TaxProvider $provider = null) {}

    public function calculate(int $amount, TaxSettings $settings, array $address): TaxResult
    {
        return ($this->provider ?? new \App\Services\Tax\ManualTaxProvider)->calculate(new TaxCalculationRequest([['amount' => $amount]], 0, $address, $settings));
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        return $rateBasisPoints > 0 ? intdiv($grossAmount * $rateBasisPoints, 10000 + $rateBasisPoints) : 0;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return intdiv($netAmount * $rateBasisPoints, 10000);
    }
}
