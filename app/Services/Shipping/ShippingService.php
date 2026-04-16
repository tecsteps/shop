<?php

namespace App\Services\Shipping;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Support\Collection;

class ShippingService
{
    public function ratesForCountry(Store $store, string $countryCode): Collection
    {
        $zones = ShippingZone::where('store_id', $store->id)
            ->with('rates')
            ->get()
            ->filter(function (ShippingZone $zone) use ($countryCode) {
                $countries = $zone->countries_json ?? [];

                return in_array('*', $countries, true) || in_array(strtoupper($countryCode), array_map('strtoupper', $countries), true);
            });

        return $zones->flatMap(fn (ShippingZone $zone) => $zone->rates->where('is_active', true)->values());
    }

    public function findRate(int $rateId): ?ShippingRate
    {
        return ShippingRate::find($rateId);
    }
}
