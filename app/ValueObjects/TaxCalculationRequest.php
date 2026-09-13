<?php

namespace App\ValueObjects;

class TaxCalculationRequest
{
    /**
     * @param  array<int, array{amount: int, variant_id?: ?int}>  $lineItems  Discounted line amounts in cents.
     * @param  array<string, mixed>  $address
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public array $lineItems,
        public int $shippingAmount,
        public array $address,
        public bool $pricesIncludeTax,
        public int $rate,
        public string $rateName,
        public bool $shippingTaxed,
        public array $config = [],
    ) {}
}
