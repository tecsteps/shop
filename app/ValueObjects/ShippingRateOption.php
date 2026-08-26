<?php

namespace App\ValueObjects;

class ShippingRateOption
{
    public function __construct(
        public int $id,
        public string $name,
        public int $amount,
        public string $type,
    ) {}

    /**
     * @return array<string, mixed>
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
