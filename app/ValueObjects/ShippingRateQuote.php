<?php

namespace App\ValueObjects;

use App\Models\ShippingRate;

class ShippingRateQuote
{
    public function __construct(
        public readonly ShippingRate $rate,
        public readonly int $amount,
        public readonly string $currency,
    ) {}

    /**
     * @return array{id: int, name: string, type: string, price_amount: int, currency: string, estimated_days_min: int|null, estimated_days_max: int|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->rate->id,
            'name' => $this->rate->name,
            'type' => $this->rate->type->value,
            'price_amount' => $this->amount,
            'currency' => $this->currency,
            'estimated_days_min' => $this->rate->config_json['estimated_days_min'] ?? null,
            'estimated_days_max' => $this->rate->config_json['estimated_days_max'] ?? null,
        ];
    }
}
