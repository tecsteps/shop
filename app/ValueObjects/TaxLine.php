<?php

namespace App\ValueObjects;

readonly class TaxLine
{
    public function __construct(
        public string $name,
        public int $rate,
        public int $amount,
    ) {}
}
