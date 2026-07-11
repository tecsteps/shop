<?php

namespace App\ValueObjects;

use JsonSerializable;

final readonly class TaxLine implements JsonSerializable
{
    public function __construct(public string $name, public int $rate, public int $amount) {}

    /** @return array{name: string, rate: int, amount: int} */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'rate' => $this->rate, 'amount' => $this->amount];
    }
}
