<?php

namespace App\Services;

use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use BackedEnum;

final class TaxCalculator
{
    public function __construct(
        private readonly ManualTaxProvider $manual,
        private readonly StripeTaxProvider $stripe,
    ) {}

    public function calculate(int $amount, mixed $settings, array $address = []): TaxCalculationResult
    {
        return $this->calculateLines([$amount], 0, $settings, $address);
    }

    /** @param list<int> $lineAmounts @param array<string, mixed> $address */
    public function calculateLines(array $lineAmounts, int $shippingAmount, mixed $settings, array $address = []): TaxCalculationResult
    {
        $request = new TaxCalculationRequest($lineAmounts, $shippingAmount, $address, $settings);
        $mode = $settings?->mode instanceof BackedEnum ? $settings->mode->value : ($settings?->mode ?? 'manual');

        return $mode === 'provider' ? $this->stripe->calculate($request) : $this->manual->calculate($request);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount < 1 || $rateBasisPoints < 1) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return $rateBasisPoints < 1 ? 0 : (int) round($netAmount * $rateBasisPoints / 10000);
    }
}
