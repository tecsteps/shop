<?php

namespace App\ValueObjects;

final readonly class TaxResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
        public int $rate,
    ) {}

    /**
     * @return array{tax_lines: array<int, array{name: string, rate: int, amount: int}>, tax_total: int, rate: int}
     */
    public function toArray(): array
    {
        return [
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'tax_total' => $this->totalAmount,
            'rate' => $this->rate,
        ];
    }
}
