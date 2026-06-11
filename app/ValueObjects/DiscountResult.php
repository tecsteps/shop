<?php

namespace App\ValueObjects;

final readonly class DiscountResult
{
    /**
     * @param  array<int, int>  $allocations  Discount amount in minor units keyed by cart line id
     */
    public function __construct(
        public int $amount,
        public bool $freeShipping,
        public array $allocations = [],
    ) {}

    public static function none(): self
    {
        return new self(0, false);
    }
}
