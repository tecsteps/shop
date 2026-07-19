<?php

namespace App\ValueObjects;

/**
 * Complete output of the deterministic pricing pipeline (spec 05 §5).
 * All amounts are integers in minor units.
 *
 * For tax-inclusive stores (prices_include_tax = true) subtotal/shipping are
 * gross amounts and taxTotal is the extracted portion already contained in
 * them, so `total = subtotal - discount + shipping`. For tax-exclusive
 * stores `total = subtotal - discount + shipping + taxTotal`.
 */
readonly class PricingResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     * @param  array<int, int>  $lineDiscounts  discount amount per line index
     */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
        public array $lineDiscounts = [],
    ) {}

    /**
     * Snapshot structure stored in checkouts.totals_json.
     *
     * @return array{subtotal: int, discount: int, shipping: int, tax: int, tax_lines: array<int, array{name: string, rate: int, amount: int}>, total: int, currency: string}
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax' => $this->taxTotal,
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
