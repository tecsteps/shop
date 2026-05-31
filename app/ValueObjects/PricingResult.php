<?php

namespace App\ValueObjects;

/**
 * The complete, deterministic output of the pricing pipeline.
 *
 * All monetary fields are integers in minor units (cents). `total` is the
 * discounted subtotal plus shipping plus tax total. The snapshot stored in
 * `checkouts.totals_json` mirrors {@see self::toArray()}.
 */
final readonly class PricingResult
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

    /**
     * The subtotal remaining after the discount has been applied.
     */
    public function discountedSubtotal(): int
    {
        return $this->subtotal - $this->discount;
    }

    /**
     * @return array{
     *     subtotal: int,
     *     discount: int,
     *     shipping: int,
     *     tax_lines: list<array{name: string, rate: int, amount: int}>,
     *     tax: int,
     *     total: int,
     *     currency: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'tax' => $this->taxTotal,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
