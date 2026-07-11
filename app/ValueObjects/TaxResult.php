<?php

namespace App\ValueObjects;

final readonly class TaxResult
{
    /** @param list<TaxLine> $lines */
    public function __construct(public int $netAmount, public int $taxAmount, public int $grossAmount, public array $lines = []) {}
}
