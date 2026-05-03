<?php

namespace App\ValueObjects;

class TaxLine
{
    public function __construct(
        public readonly string $name,
        public readonly int $rate,
        public readonly int $amount,
    ) {}

    /**
     * @return array{name: string, rate: int, amount: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'rate' => $this->rate,
            'amount' => $this->amount,
        ];
    }
}
