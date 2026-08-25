<?php

namespace App\ValueObjects;

class TaxCalculationResult
{
    /**
     * @param  list<TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
    ) {}
}
