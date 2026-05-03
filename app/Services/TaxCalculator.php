<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\TaxResult;

class TaxCalculator
{
    public function __construct(
        private readonly ManualTaxProvider $manual,
        private readonly StripeTaxProvider $stripe,
    ) {}

    /**
     * @param  array<string, mixed>  $address
     */
    public function calculate(int $amount, TaxSettings $settings, array $address): TaxResult
    {
        return $this->calculateForAmounts([$amount], 0, $settings, $address);
    }

    /**
     * @param  array<int, int>  $amounts
     * @param  array<string, mixed>  $address
     */
    public function calculateForAmounts(array $amounts, int $shippingAmount, TaxSettings $settings, array $address): TaxResult
    {
        if ($settings->mode === TaxMode::Provider && $settings->provider === 'stripe_tax') {
            return $this->stripe->calculate($amounts, $shippingAmount, $settings, $address);
        }

        return $this->manual->calculate($amounts, $shippingAmount, $settings, $address);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        return $this->manual->extractInclusive($grossAmount, $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return $this->manual->addExclusive($netAmount, $rateBasisPoints);
    }
}
