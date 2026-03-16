<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\Address;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

class TaxCalculator
{
    /**
     * @param  array<array{amount: int, quantity: int}>  $lineItems
     */
    public function calculate(array $lineItems, int $shippingAmount, TaxSettings $taxSettings, Address $address): TaxCalculationResult
    {
        $request = new TaxCalculationRequest(
            lineItems: $lineItems,
            shippingAmount: $shippingAmount,
            address: $address,
            taxSettings: $taxSettings,
        );

        $provider = match ($taxSettings->mode) {
            TaxMode::Provider => new StripeTaxProvider,
            default => new ManualTaxProvider,
        };

        return $provider->calculate($request);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        $netAmount = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $netAmount;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }
}
