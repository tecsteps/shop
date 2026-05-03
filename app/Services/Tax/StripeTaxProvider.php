<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\Models\TaxSettings;
use App\ValueObjects\TaxResult;
use RuntimeException;

class StripeTaxProvider implements TaxProvider
{
    /**
     * @param  array<int, int>  $amounts
     * @param  array<string, mixed>  $address
     */
    public function calculate(array $amounts, int $shippingAmount, TaxSettings $settings, array $address): TaxResult
    {
        if (data_get($settings->config_json, 'fallback') === 'block') {
            throw new RuntimeException('Stripe Tax provider is not configured.');
        }

        return new TaxResult([], 0, 0);
    }
}
