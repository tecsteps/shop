<?php

namespace App\ValueObjects;

readonly class TaxResult
{
    /** @param list<TaxLine> $lines */
    public function __construct(public int $total, public array $lines = []) {}
}
