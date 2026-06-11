<?php

namespace App\ValueObjects;

final readonly class TaxResult
{
    /**
     * @param  list<TaxLine>  $taxLines
     */
    public function __construct(
        public array $taxLines,
        public int $taxTotal,
    ) {}

    public static function zero(): self
    {
        return new self([], 0);
    }

    /**
     * @return array{tax_lines: list<array{name: string, rate: int, amount: int}>, tax_total: int}
     */
    public function toArray(): array
    {
        return [
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'tax_total' => $this->taxTotal,
        ];
    }
}
