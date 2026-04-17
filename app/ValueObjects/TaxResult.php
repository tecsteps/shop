<?php

namespace App\ValueObjects;

final readonly class TaxResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     */
    public function __construct(
        public int $taxAmount,
        public array $taxLines,
    ) {}
}
