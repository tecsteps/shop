<?php

namespace App\ValueObjects;

readonly class TaxLine
{
    public function __construct(
        public string $name,
        public int $rate,
        public int $amount,
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
