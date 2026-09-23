<?php

namespace App\ValueObjects;

final readonly class TaxCalculationResult
{
    /**
     * @param  list<TaxLine>  $lines
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public array $lines,
        public int $total,
        public array $snapshot,
    ) {}
}
