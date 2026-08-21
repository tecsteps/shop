<?php

namespace App\ValueObjects;

readonly class DiscountResult
{
    /** @param array<int, int> $allocations */
    public function __construct(public int $amount, public array $allocations = [], public bool $freeShipping = false) {}
}
