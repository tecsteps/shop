<?php

namespace App\ValueObjects;

/**
 * Output of a tax provider (spec 05 §8.1): aggregated tax lines plus the
 * per-line detail used for the checkout tax_provider_snapshot_json.
 */
readonly class TaxCalculationResult
{
    /**
     * @param  array<int, TaxLine>  $taxLines
     * @param  array<int, array{variant_id: int|null, tax_amount: int, rate: int, jurisdiction: string|null}>  $lineDetails
     */
    public function __construct(
        public array $taxLines,
        public int $totalAmount,
        public array $lineDetails = [],
        public int $shippingTaxAmount = 0,
        public int $shippingTaxRate = 0,
    ) {}

    /**
     * Zero-tax result.
     */
    public static function zero(): self
    {
        return new self(taxLines: [], totalAmount: 0);
    }
}
