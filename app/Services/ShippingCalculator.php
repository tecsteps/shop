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
     * @param  array{country_code?: ?string, province_code?: ?string}  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address, ?Cart $cart = null): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        if (! $zone) {
            return collect();
        }

        $rates = $zone->rates()->where('is_active', true)->get();

        if (! $cart) {
            return $rates;
        }

        return $rates->filter(fn (ShippingRate $rate): bool => $this->calculate($rate, $cart) !== null)->values();
    }

    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $country = $address['country_code'] ?? null;
        $region = $address['province_code'] ?? null;

        if (! $country) {
            return null;
        }

        $zones = ShippingZone::query()->where('store_id', $store->id)->get();

        $best = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = (array) ($zone->countries_json ?? []);
            $regions = (array) ($zone->regions_json ?? []);

            if (! in_array($country, $countries, true)) {
                continue;
            }

            $specificity = 1;
            if ($region && in_array($region, $regions, true)) {
                $specificity = 2;
            }

            if ($specificity > $bestSpecificity) {
                $best = $zone;
                $bestSpecificity = $specificity;
            } elseif ($specificity === $bestSpecificity && $best && $zone->id < $best->id) {
                $best = $zone;
            }
        }

        return $best;
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $cart->loadMissing('lines.variant');
        $config = (array) ($rate->config_json ?? []);

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($config['amount'] ?? 0),
            ShippingRateType::Weight => $this->calculateWeight($config, $cart),
            ShippingRateType::Price => $this->calculatePrice($config, $cart),
            ShippingRateType::Carrier => $this->calculateCarrier($config),
        };
    }

    protected function calculateWeight(array $config, Cart $cart): ?int
    {
        $ranges = $config['ranges'] ?? [];
        $weight = 0;

        foreach ($cart->lines as $line) {
            $variant = $line->variant;
            if ($variant && $variant->requires_shipping) {
                $weight += (int) ($variant->weight_g ?? 0) * $line->quantity;
            }
        }

        foreach ($ranges as $range) {
            $min = (int) ($range['min_g'] ?? 0);
            $max = (int) ($range['max_g'] ?? PHP_INT_MAX);
            if ($weight >= $min && $weight <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return null;
    }

    protected function calculatePrice(array $config, Cart $cart): ?int
    {
        $ranges = $config['ranges'] ?? [];
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

        foreach ($ranges as $range) {
            $min = (int) ($range['min_amount'] ?? 0);
            if ($subtotal < $min) {
                continue;
            }

            if (! array_key_exists('max_amount', $range) || $subtotal <= (int) $range['max_amount']) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return null;
    }

    protected function calculateCarrier(array $config): int
    {
        return (int) ($config['fallback_amount'] ?? 999);
    }

    public function requiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.variant');

        foreach ($cart->lines as $line) {
            if ($line->variant && $line->variant->requires_shipping) {
                return true;
            }
        }

        return false;
    }
}
