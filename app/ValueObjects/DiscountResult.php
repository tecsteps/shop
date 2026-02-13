<?php

namespace App\ValueObjects;

readonly class DiscountResult
{
    /**
     * @param  array<int, int>  $lineAllocations
     */
    public function __construct(
        public int $totalDiscount,
        public bool $freeShipping,
        public array $lineAllocations,
    ) {}

    /**
     * @return array{total_discount: int, free_shipping: bool, line_allocations: array<int, int>}
     */
    public function toArray(): array
    {
        return [
            'total_discount' => $this->totalDiscount,
            'free_shipping' => $this->freeShipping,
            'line_allocations' => $this->lineAllocations,
        ];
    }
}
