<?php

namespace App\ValueObjects;

use App\Models\TaxSettings;

final readonly class TaxCalculationRequest
{
    /** @param list<int> $lineItems @param array<string, mixed> $address */
    public function __construct(
        public array $lineItems,
        public int $shippingAmount,
        public array $address,
        public ?TaxSettings $taxSettings,
    ) {}
}
