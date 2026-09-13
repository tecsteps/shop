<?php

namespace App\ValueObjects;

class PricingResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     * @param  array<int, array<string, mixed>>  $appliedDiscounts
     */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
        public array $appliedDiscounts = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax' => $this->taxTotal,
            'total' => $this->total,
            'currency' => $this->currency,
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'applied_discounts' => $this->appliedDiscounts,
        ];
    }
}
