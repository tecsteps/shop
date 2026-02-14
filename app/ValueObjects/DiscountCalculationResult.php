<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class DiscountCalculationResult implements Arrayable
{
    /**
     * @param  array<int, int>  $lineAllocations
     */
    public function __construct(
        public int $amount,
        public array $lineAllocations,
        public bool $freeShipping = false,
    ) {}

    public static function none(): self
    {
        return new self(amount: 0, lineAllocations: [], freeShipping: false);
    }

    public function amountForLine(int $lineId): int
    {
        return $this->lineAllocations[$lineId] ?? 0;
    }

    /**
     * @return array{amount: int, line_allocations: array<int, int>, free_shipping: bool}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'line_allocations' => $this->lineAllocations,
            'free_shipping' => $this->freeShipping,
        ];
    }
}
