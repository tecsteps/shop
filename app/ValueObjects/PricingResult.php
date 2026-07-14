<?php

namespace App\ValueObjects;

use JsonSerializable;

final readonly class PricingResult implements JsonSerializable
{
    /** @param list<TaxLine> $taxLines */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
    ) {}

    /** @return array{subtotal: int, discount: int, discounted_subtotal: int, shipping: int, tax_lines: list<array{name: string, rate: int, amount: int}>, tax_total: int, tax: int, total: int, currency: string} */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'discounted_subtotal' => max(0, $this->subtotal - $this->discount),
            'shipping' => $this->shipping,
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'tax_total' => $this->taxTotal,
            'tax' => $this->taxTotal,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
