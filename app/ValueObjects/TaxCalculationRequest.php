<?php

namespace App\ValueObjects;

use App\Models\TaxSettings;

/**
 * Input for a {@see \App\Contracts\TaxProvider}.
 *
 * `lineAmounts` are the post-discount taxable amounts per line (cents).
 * `shippingAmount` is the taxable shipping cost. `address` is the structured
 * shipping address used for rate resolution. `taxSettings` carries the store's
 * tax mode and configuration.
 */
final readonly class TaxCalculationRequest
{
    /**
     * @param  list<int>  $lineAmounts
     * @param  array<string, mixed>  $address
     */
    public function __construct(
        public array $lineAmounts,
        public int $shippingAmount,
        public array $address,
        public TaxSettings $taxSettings,
    ) {}
}
