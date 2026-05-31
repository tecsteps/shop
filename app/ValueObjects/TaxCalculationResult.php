<?php

namespace App\ValueObjects;

/**
 * Output from a {@see \App\Contracts\TaxProvider}.
 */
final readonly class TaxCalculationResult
{
    /**
     * @param  list<TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
    ) {}
}
