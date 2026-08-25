<?php

namespace App\ValueObjects;

class TaxCalculationRequest
{
    /**
     * @param  list<array<string, mixed>>  $lineItems
     */
    public function __construct(
        public array $lineItems,
        public int $shippingAmount,
        public ?Address $address,
        public mixed $taxSettings,
    ) {}
}
