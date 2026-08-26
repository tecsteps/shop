<?php

namespace App\ValueObjects;

class DiscountResult
{
    /**
     * @param  array<int, int>  $allocations  map of cart_line_id => discount amount
     */
    public function __construct(
        public int $amount,
        public array $allocations = [],
        public bool $freeShipping = false,
    ) {}
}
