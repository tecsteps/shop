<?php

namespace App\ValueObjects;

final readonly class TaxCalculationRequest
{
    /**
     * @param  list<int>  $lineAmounts  Amounts after discounts, in minor currency units.
     * @param  array<string, mixed>  $address
     * @param  array<string, mixed>  $rates  Country and province rates in basis points.
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(
        public int $storeId,
        public array $lineAmounts,
        public int $shippingAmount,
        public array $address,
        public array $rates,
        public int $defaultRate,
        public bool $pricesIncludeTax,
        public bool $shippingTaxable,
        public string $currency,
        public array $configuration = [],
    ) {}
}
