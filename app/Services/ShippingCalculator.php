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
     * @param  array{country_code?: string, province_code?: string}  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $countryCode = $address['country_code'] ?? null;
        $provinceCode = $address['province_code'] ?? null;

        if (! $countryCode) {
            return collect();
        }

        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

        $matchedZones = [];

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $matchesCountry = in_array($countryCode, $countries);
            $matchesRegion = $provinceCode && in_array($provinceCode, $regions);

            if ($matchesCountry && $matchesRegion) {
                $matchedZones[] = ['zone' => $zone, 'specificity' => 2];
            } elseif ($matchesCountry) {
                $matchedZones[] = ['zone' => $zone, 'specificity' => 1];
            }
        }

        if (empty($matchedZones)) {
            return collect();
        }

        $maxSpecificity = max(array_column($matchedZones, 'specificity'));

        $bestZones = array_filter($matchedZones, fn (array $m): bool => $m['specificity'] === $maxSpecificity);

        usort($bestZones, fn (array $a, array $b): int => $a['zone']->id <=> $b['zone']->id);

        $rates = collect();
        foreach ($bestZones as $match) {
            $zoneRates = $match['zone']->rates()->where('is_active', true)->get();
            $rates = $rates->merge($zoneRates);
        }

        return $rates;
    }

    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => $this->calculateFlat($config),
            ShippingRateType::Weight => $this->calculateWeight($config, $cart),
            ShippingRateType::Price => $this->calculatePrice($config, $cart),
            ShippingRateType::Carrier => $config['amount'] ?? 0,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculateFlat(array $config): int
    {
        return (int) ($config['amount'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculateWeight(array $config, Cart $cart): int
    {
        $cart->load('lines.variant');

        $totalWeight = 0;
        foreach ($cart->lines as $line) {
            $totalWeight += ($line->variant->weight_g ?? 0) * $line->quantity;
        }

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            $min = $range['min_g'] ?? 0;
            $max = $range['max_g'] ?? PHP_INT_MAX;

            if ($totalWeight >= $min && $totalWeight <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return (int) ($config['default_amount'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculatePrice(array $config, Cart $cart): int
    {
        $subtotal = $cart->lines->sum('line_subtotal_amount');

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            $min = $range['min_amount'] ?? 0;
            $max = $range['max_amount'] ?? PHP_INT_MAX;

            if ($subtotal >= $min && $subtotal <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return (int) ($config['default_amount'] ?? 0);
    }
}
