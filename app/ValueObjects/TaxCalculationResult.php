<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class TaxCalculationResult implements Arrayable
{
    /**
     * @param  list<TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
        public int $rateBasisPoints,
    ) {}

    public static function zero(): self
    {
        return new self(taxLines: [], totalAmount: 0, rateBasisPoints: 0);
    }

    /**
     * @return array{tax_lines: list<array{name: string, rate: int, amount: int}>, total_amount: int, rate_basis_points: int}
     */
    public function toArray(): array
    {
        return [
            'tax_lines' => array_map(static fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'total_amount' => $this->totalAmount,
            'rate_basis_points' => $this->rateBasisPoints,
        ];
    }
}
