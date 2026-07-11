<?php

namespace App\ValueObjects;

use JsonSerializable;

final readonly class PricingResult implements JsonSerializable
{
    /** @param list<TaxLine> $taxLines */
    public function __construct(public int $subtotal, public int $discount, public int $shipping, public array $taxLines, public int $taxTotal, public int $total, public string $currency) {}

    /** @return array{subtotal: int, discount: int, shipping: int, tax_lines: list<TaxLine>, tax_total: int, total: int, currency: string} */
    public function jsonSerialize(): array
    {
        return ['subtotal' => $this->subtotal, 'discount' => $this->discount, 'shipping' => $this->shipping, 'tax_lines' => $this->taxLines, 'tax_total' => $this->taxTotal, 'total' => $this->total, 'currency' => $this->currency];
    }
}
