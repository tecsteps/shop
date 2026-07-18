<?php

namespace App\ValueObjects;

use App\Models\Discount;

readonly class DiscountResult
{
    /** @param array<int, int> $allocations */
    public function __construct(
        public ?Discount $discount,
        public int $amount,
        public array $allocations = [],
        public bool $freeShipping = false,
    ) {}
}
