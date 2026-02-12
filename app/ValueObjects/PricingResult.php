<?php

namespace App\ValueObjects;

readonly class PricingResult
{
    /**
     * @param  list<TaxLine>  $taxLines
     */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
    ) {}

    /** @return array{subtotal: int, discount: int, shipping: int, tax_lines: list<array{name: string, rate: int, amount: int}>, tax_total: int, total: int, currency: string} */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax_lines' => array_map(fn (TaxLine $tl) => $tl->toArray(), $this->taxLines),
            'tax_total' => $this->taxTotal,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
