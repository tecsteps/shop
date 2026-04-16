<?php

namespace App\Services\Pricing;

class CartTotals
{
    public function __construct(
        public readonly int $subtotal,
        public readonly int $discount,
        public readonly int $shipping,
        public readonly int $tax,
        public readonly int $total,
        public readonly string $currency,
    ) {}

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax' => $this->tax,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
