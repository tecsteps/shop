<?php

namespace App\ValueObjects;

use App\Models\TaxSettings;

/**
 * Input passed to a tax provider (spec 05 §8.1).
 */
readonly class TaxCalculationRequest
{
    /**
     * @param  array<int, array{variant_id: int|null, amount: int}>  $lineItems  discounted line amounts
     */
    public function __construct(
        public array $lineItems,
        public int $shippingAmount,
        public ?Address $address,
        public TaxSettings $taxSettings,
    ) {}
}
