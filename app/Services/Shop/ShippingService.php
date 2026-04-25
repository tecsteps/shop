<?php

namespace App\Services\Shop;

use App\Models\ShippingRate;
use App\Models\ShippingZone;

class ShippingService
{
    public function rateForCountry(string $countryCode, int $subtotal): ?ShippingRate
    {
        $zone = ShippingZone::query()
            ->with('rates')
            ->get()
            ->first(fn (ShippingZone $zone): bool => in_array(strtoupper($countryCode), $zone->countries, true) || in_array('*', $zone->countries, true));

        return $zone?->rates
            ->filter(fn (ShippingRate $rate): bool => $rate->min_order_amount === null || $subtotal >= $rate->min_order_amount)
            ->sortBy('price_amount')
            ->first();
    }
}

