<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class PricingResult implements Arrayable
{
    /**
     * @param  list<TaxLine>  $taxLines
     * @param  array<int, int>  $lineDiscountAllocations
     */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
        public array $lineDiscountAllocations = [],
        public bool $freeShippingApplied = false,
    ) {}

    /**
     * @return array{
     *     subtotal: int,
     *     discount: int,
     *     shipping: int,
     *     tax: int,
     *     tax_lines: list<array{name: string, rate: int, amount: int}>,
     *     total: int,
     *     currency: string,
     *     line_discount_allocations: array<int, int>,
     *     free_shipping_applied: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax' => $this->taxTotal,
            'tax_lines' => array_map(static fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'total' => $this->total,
            'currency' => $this->currency,
            'line_discount_allocations' => $this->lineDiscountAllocations,
            'free_shipping_applied' => $this->freeShippingApplied,
        ];
    }
}
