<?php

namespace App\ValueObjects;

readonly class TaxLine
{
    public function __construct(
        public string $name,
        public int $rate,
        public int $amount
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

    /**
     * @param  array{name: string, rate: int, amount: int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            rate: $data['rate'],
            amount: $data['amount'],
        );
    }
}
