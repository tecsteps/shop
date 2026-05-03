<?php

namespace App\ValueObjects;

class PricingResult
{
    /**
     * @param  list<TaxLine>  $taxLines
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
     * @return array{subtotal: int, discount: int, shipping: int, tax: int, tax_lines: list<array{name: string, rate: int, amount: int}>, total: int, currency: string}
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax' => $this->taxTotal,
            'tax_lines' => array_map(
                fn (TaxLine $line): array => $line->toArray(),
                $this->taxLines,
            ),
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
