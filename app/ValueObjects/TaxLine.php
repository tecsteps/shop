<?php

namespace App\ValueObjects;

/**
 * A single tax line item within a pricing calculation.
 *
 * `rate` is in basis points (e.g. 1900 = 19.00%); `amount` is in minor units
 * (cents).
 */
final readonly class TaxLine
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
