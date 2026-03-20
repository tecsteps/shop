<?php

namespace App\ValueObjects;

final readonly class DiscountResult
{
    /**
     * @param  array<int, int>  $lineAllocations  Map of cart_line_id => discount amount
     */
    public function __construct(
        public int $totalDiscount,
        public array $lineAllocations,
        public bool $isFreeShipping = false,
    ) {}
}
