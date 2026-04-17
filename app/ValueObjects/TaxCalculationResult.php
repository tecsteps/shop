<?php

namespace App\ValueObjects;

readonly class TaxCalculationResult
{
    /**
     * @param  array<TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount
    ) {}
}
