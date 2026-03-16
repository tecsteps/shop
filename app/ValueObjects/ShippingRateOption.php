<?php

namespace App\ValueObjects;

readonly class ShippingRateOption
{
    public function __construct(
        public int $id,
        public string $name,
        public int $amount,
        public string $type
    ) {}

    /**
     * @return array{id: int, name: string, amount: int, type: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'type' => $this->type,
        ];
    }
}
