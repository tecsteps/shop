<?php

namespace App\ValueObjects;

use JsonSerializable;

final readonly class TaxCalculationResult implements JsonSerializable
{
    /** @param list<TaxLine> $taxLines */
    public function __construct(public array $taxLines, public int $totalAmount) {}

    /** @return array{tax_lines: list<array{name: string, rate: int, amount: int}>, total_amount: int} */
    public function jsonSerialize(): array
    {
        return [
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'total_amount' => $this->totalAmount,
        ];
    }
}
