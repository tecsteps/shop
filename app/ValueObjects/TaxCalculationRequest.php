<?php

namespace App\ValueObjects;

use App\Models\TaxSettings;

final readonly class TaxCalculationRequest
{
    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    public function __construct(
        public array $lineItems,
        public int $shippingAmount,
        public array $address,
        public TaxSettings $settings,
    ) {}
}
