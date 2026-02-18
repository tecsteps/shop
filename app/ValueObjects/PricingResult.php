<?php

namespace App\ValueObjects;

class PricingResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     */
    public function __construct(
        public readonly int $subtotal,
        public readonly int $discount,
        public readonly int $shipping,
        public readonly array $taxLines,
        public readonly int $taxTotal,
        public readonly int $total,
        public readonly string $currency,
    ) {}

    /**
     * @return array{subtotal: int, discount: int, shipping: int, tax_lines: array<int, array{name: string, rate: int, amount: int}>, tax_total: int, total: int, currency: string}
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax_lines' => array_map(fn (TaxLine $line) => $line->toArray(), $this->taxLines),
            'tax_total' => $this->taxTotal,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
