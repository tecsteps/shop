<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\Store;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $country = strtoupper((string) ($address['country_code'] ?? ''));
        $region = strtoupper((string) ($address['province_code'] ?? ''));

        $rates = ShippingRate::query()->where('is_active', true)->whereHas('zone', fn ($query) => $query->where('store_id', $store->getKey()))->with('zone')->get();

        $matching = $rates->filter(function (ShippingRate $rate) use ($country, $region): bool {
            $countries = array_map('strtoupper', $rate->zone->countries_json ?? []);
            $regions = array_map('strtoupper', $rate->zone->regions_json ?? []);

            return $country !== '' && in_array($country, $countries, true)
                && ($region === '' || $regions === [] || in_array($region, $regions, true));
        });

        $specificity = $matching->groupBy(function (ShippingRate $rate) use ($region): int {
            $countries = array_map('strtoupper', $rate->zone->countries_json ?? []);
            $regions = array_map('strtoupper', $rate->zone->regions_json ?? []);

            return $region !== '' && in_array($region, $regions, true) ? 2 : 1;
        });

        return $specificity->sortKeysDesc()->first()?->sortBy('id')->values() ?? collect();
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $lines = $cart->load('lines.variant')->lines;
        $lines = $lines->filter(fn ($line): bool => (bool) $line->variant->requires_shipping);
        $weight = (int) $lines->sum(fn ($line): int => $line->quantity * ($line->variant->weight_g ?? $line->variant->weight_grams));
        $subtotal = (int) $lines->sum('line_total_amount');
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            'weight' => $this->rangeAmount($config['ranges'] ?? [], $weight, $rate->price_amount),
            'price' => $this->rangeAmount($config['ranges'] ?? [], $subtotal, $rate->price_amount),
            'carrier' => (int) ($config['amount'] ?? $rate->price_amount ?? 0),
            default => (int) ($config['amount'] ?? $rate->price_amount ?? 0),
        };
    }

    private function rangeAmount(array $ranges, int $value, ?int $fallback): ?int
    {
        foreach ($ranges as $range) {
            if ($value >= (int) ($range['min_g'] ?? $range['min_amount'] ?? 0) && $value <= (int) ($range['max_g'] ?? $range['max_amount'] ?? PHP_INT_MAX)) {
                return (int) ($range['amount'] ?? $fallback ?? 0);
            }
        }

        return null;
    }
}
