<?php

namespace App\ValueObjects;

use App\Models\TaxSettings;

readonly class TaxCalculationRequest
{
    /**
     * @param  array<array{amount: int, quantity: int}>  $lineItems
     */
    public function __construct(
        public array $lineItems,
        public int $shippingAmount,
        public Address $address,
        public TaxSettings $taxSettings
    ) {}
}
