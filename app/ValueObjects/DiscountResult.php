<?php

namespace App\ValueObjects;

class DiscountResult
{
    /**
     * @param  array<int, int>  $allocations  Map of cart_line_id => discount amount
     */
    public function __construct(
        public readonly int $amount,
        public readonly bool $isFreeShipping,
        public readonly array $allocations = [],
    ) {}
}
