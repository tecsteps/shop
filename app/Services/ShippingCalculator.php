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
    /**
     * Get available shipping rates for a given address.
     *
     * @param  array{country_code?: string, province_code?: string}  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $countryCode = $address['country_code'] ?? '';
        $provinceCode = $address['province_code'] ?? '';

        $zones = ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        $matchingZones = $zones->filter(function (ShippingZone $zone) use ($countryCode, $provinceCode) {
            return $this->zoneMatchesAddress($zone, $countryCode, $provinceCode);
        });

        if ($matchingZones->isEmpty()) {
            return collect();
        }

        /** @var Collection<int, ShippingRate> $rates */
        $rates = ShippingRate::query()
            ->whereIn('zone_id', $matchingZones->pluck('id'))
            ->where('is_active', true)
            ->get();

        return $rates;
    }

    /**
     * Calculate shipping cost based on rate type.
     */
    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        $cart->loadMissing('lines.variant');

        $requiresShipping = $cart->lines->contains(fn ($line) => $line->variant->requires_shipping);
        if (! $requiresShipping) {
            return 0;
        }

        return match ($rate->type) {
            ShippingRateType::Flat => $this->calculateFlat($rate),
            ShippingRateType::Weight => $this->calculateWeight($rate, $cart),
            ShippingRateType::Price => $this->calculatePrice($rate, $cart),
            ShippingRateType::Carrier => $this->calculateFlat($rate),
        };
    }

    private function zoneMatchesAddress(ShippingZone $zone, string $countryCode, string $provinceCode): bool
    {
        $countries = $zone->countries_json ?? [];
        $regions = $zone->regions_json ?? [];

        if (! empty($regions) && in_array($provinceCode, $regions, true)) {
            return true;
        }

        if (! empty($countries) && in_array($countryCode, $countries, true)) {
            return true;
        }

        return false;
    }

    private function calculateFlat(ShippingRate $rate): int
    {
        /** @var array{amount?: int} $config */
        $config = $rate->config_json;

        return $config['amount'] ?? 0;
    }

    private function calculateWeight(ShippingRate $rate, Cart $cart): int
    {
        $totalWeight = $cart->lines->sum(function ($line) {
            return ($line->variant->weight_g ?? 0) * $line->quantity;
        });

        $config = $rate->config_json;
        /** @var array<int, array{min_g: int, max_g: int, amount: int}> $ranges */
        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            if ($totalWeight >= $range['min_g'] && $totalWeight <= $range['max_g']) {
                return $range['amount'];
            }
        }

        if (! empty($ranges)) {
            $lastRange = end($ranges);

            return $lastRange['amount'];
        }

        return 0;
    }

    private function calculatePrice(ShippingRate $rate, Cart $cart): int
    {
        $subtotal = $cart->lines->sum('line_subtotal_amount');

        $config = $rate->config_json;
        /** @var array<int, array{min_amount: int, max_amount: int, amount: int}> $ranges */
        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            if ($subtotal >= $range['min_amount'] && $subtotal <= $range['max_amount']) {
                return $range['amount'];
            }
        }

        if (! empty($ranges)) {
            $lastRange = end($ranges);

            return $lastRange['amount'];
        }

        return 0;
    }
}
