<?php

namespace App\ValueObjects;

final readonly class PricingResult
{
    /**
     * @param  list<TaxLine>  $taxLines
     * @param  array<int, int>  $discountAllocations
     */
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public array $taxLines,
        public int $taxTotal,
        public int $total,
        public string $currency,
        public array $discountAllocations = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'taxLines' => array_map(static fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'taxTotal' => $this->taxTotal,
            'total' => $this->total,
            'currency' => $this->currency,
            'discountAllocations' => $this->discountAllocations,
        ];
    }
}
