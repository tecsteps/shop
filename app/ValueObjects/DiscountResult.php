<?php

namespace App\ValueObjects;

use App\Models\Discount;

final class DiscountResult
{
    /**
     * @param  array<int, array{line_id: int, amount: int}>  $allocations
     */
    public function __construct(
        public readonly Discount $discount,
        public readonly int $totalAmount,
        public readonly bool $freeShipping,
        public readonly array $allocations,
    ) {}
}
