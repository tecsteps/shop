<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\Store;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $country = $address['country_code'] ?? $address['country'] ?? '';

        return \App\Models\ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['rates' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->filter(function ($zone) use ($country) {
                if (empty($zone->countries_json)) {
                    return true;
                }

                return in_array($country, $zone->countries_json);
            })
            ->flatMap->rates;
    }

    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        return $rate->amount;
    }
}
