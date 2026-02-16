<?php

namespace App\ValueObjects;

readonly class PricingResult
{
    /**
     * @param  array<TaxLine>  $taxLines
     */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
    ) {}
}
