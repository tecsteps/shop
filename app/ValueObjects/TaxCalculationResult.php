<?php

namespace App\ValueObjects;

use JsonSerializable;

final readonly class TaxCalculationResult implements JsonSerializable
{
    /**
     * @param  list<TaxLine>  $taxLines
     * @param  list<int>  $lineAmounts
     * @param  array<string, mixed>  $providerResponse
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
        public array $lineAmounts = [],
        public int $shippingAmount = 0,
        public string $provider = 'manual',
        public array $providerResponse = [],
    ) {}

    /** @return array{tax_lines: list<array{name: string, rate: int, amount: int}>, total_amount: int} */
    public function jsonSerialize(): array
    {
        return [
            'tax_lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $this->taxLines),
            'total_amount' => $this->totalAmount,
            'line_amounts' => $this->lineAmounts,
            'shipping_amount' => $this->shippingAmount,
            'provider' => $this->provider,
            'provider_response' => $this->providerResponse,
        ];
    }
}
