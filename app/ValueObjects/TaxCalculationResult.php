<?php

namespace App\ValueObjects;

class TaxCalculationResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
    ) {}
}
