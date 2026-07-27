<?php

namespace App\ValueObjects;

use App\Enums\ShippingRateType;

/**
 * A calculated shipping rate option (spec 05 §22 "ShippingRate").
 *
 * Named ShippingRateVO to avoid clashing with the Eloquent
 * App\Models\ShippingRate model.
 */
readonly class ShippingRateVO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $amount,
        public ShippingRateType $type,
        public ?int $estimatedDaysMin = null,
        public ?int $estimatedDaysMax = null,
    ) {}
}
