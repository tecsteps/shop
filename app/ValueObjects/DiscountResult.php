<?php

namespace App\ValueObjects;

/**
 * The outcome of applying a discount to a set of cart lines.
 *
 * `amount` is the total monetary discount in minor units (zero for a
 * free-shipping discount). `freeShipping` indicates the shipping cost should be
 * overridden to zero. `allocations` maps each affected cart line id to its share
 * of the discount (largest-remainder allocation; sums exactly to `amount`).
 */
final readonly class DiscountResult
{
    /**
     * @param  array<int, int>  $allocations
     */
    public function __construct(
        public int $amount,
        public bool $freeShipping,
        public array $allocations,
    ) {}
}
