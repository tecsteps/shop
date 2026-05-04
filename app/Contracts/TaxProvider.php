<?php

namespace App\Contracts;

use App\Models\TaxSettings;
use App\ValueObjects\TaxResult;

interface TaxProvider
{
    /**
     * @param  array<int, int>  $amounts
     * @param  array<string, mixed>  $address
     */
    public function calculate(array $amounts, int $shippingAmount, TaxSettings $settings, array $address): TaxResult;
}
