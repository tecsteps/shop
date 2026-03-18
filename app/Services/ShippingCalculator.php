<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with(['rates' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $matchingZones = collect();
        $country = $address['country'] ?? '';
        $region = $address['province_code'] ?? '';

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($country, $countries);
            $regionMatch = ! empty($region) && in_array($region, $regions);

            if ($countryMatch && $regionMatch) {
                $matchingZones->push(['zone' => $zone, 'specificity' => 2]);
            } elseif ($countryMatch) {
                $matchingZones->push(['zone' => $zone, 'specificity' => 1]);
            }
        }

        if ($matchingZones->isEmpty()) {
            return collect();
        }

        return $matchingZones
            ->sortByDesc('specificity')
            ->flatMap(fn ($entry) => $entry['zone']->rates)
            ->values();
    }

    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => $config['amount'] ?? 0,
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $cart),
            ShippingRateType::Carrier => $config['amount'] ?? 999,
        };
    }

    private function calculateWeightRate(array $config, Cart $cart): int
    {
        $totalWeight = 0;
        foreach ($cart->lines as $line) {
            $variant = $line->variant;
            if ($variant && $variant->requires_shipping) {
                $totalWeight += ($variant->weight_g ?? 0) * $line->quantity;
            }
        }

        if ($totalWeight === 0) {
            return 0;
        }

        $ranges = $config['ranges'] ?? [];
        foreach ($ranges as $range) {
            if ($totalWeight >= $range['min_g'] && $totalWeight <= $range['max_g']) {
                return $range['amount'];
            }
        }

        return 0;
    }

    private function calculatePriceRate(array $config, Cart $cart): int
    {
        $subtotal = $cart->lines->sum('line_subtotal_amount');

        $ranges = $config['ranges'] ?? [];
        foreach ($ranges as $range) {
            $min = $range['min_amount'] ?? 0;
            $max = $range['max_amount'] ?? null;

            if ($subtotal >= $min && ($max === null || $subtotal <= $max)) {
                return $range['amount'];
            }
        }

        return 0;
    }
}
