<?php

namespace App\ValueObjects;

final readonly class PricingResult
{
    /**
     * @param  list<TaxLine>  $taxLines
     * @param  array<int, int>  $discountAllocations
     * @param  list<array{discount_id: int|null, code: string|null, title: string, type: string, activation_method: string, amount: int, allocations: array<int, int>}>  $appliedDiscounts
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
        public array $appliedDiscounts = [],
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
            'appliedDiscounts' => $this->appliedDiscounts,
            'applied_discount_ids' => array_values(array_unique(array_filter(array_map(
                static fn (array $discount): ?int => $discount['discount_id'],
                $this->appliedDiscounts,
            ), static fn (?int $discountId): bool => $discountId !== null))),
        ];
    }
}
