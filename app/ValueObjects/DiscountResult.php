<?php

namespace App\ValueObjects;

class DiscountResult
{
    /**
     * @param  array<int, int>  $allocations
     */
    public function __construct(
        public readonly int $amount,
        public readonly array $allocations,
        public readonly bool $freeShipping = false,
    ) {}
}
