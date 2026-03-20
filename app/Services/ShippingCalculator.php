<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @param  array{country?: string, province_code?: string}  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        if (! $zone) {
            return collect();
        }

        return $zone->rates()->where('is_active', true)->get();
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json;

        return match ($rate->type->value) {
            'flat' => $config['amount'] ?? 0,
            'weight' => $this->calculateWeightRate($config, $cart),
            'price' => $this->calculatePriceRate($config, $cart),
            default => null,
        };
    }

    /**
     * @param  array{country?: string, province_code?: string}  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        $country = $address['country'] ?? null;
        $region = $address['province_code'] ?? null;

        if (! $country) {
            return null;
        }

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($country, $countries);

            if (! $countryMatch) {
                continue;
            }

            $regionMatch = $region && in_array($region, $regions);

            if ($countryMatch && $regionMatch) {
                $specificity = 2;
            } elseif ($countryMatch) {
                $specificity = 1;
            } else {
                continue;
            }

            if ($specificity > $bestSpecificity) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            } elseif ($specificity === $bestSpecificity && $zone->id < $bestMatch->id) {
                $bestMatch = $zone;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array{ranges?: array<int, array{min_g: int, max_g: int, amount: int}>}  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $totalWeight = $this->getTotalShippingWeight($cart);

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            if ($totalWeight >= $range['min_g'] && $totalWeight <= $range['max_g']) {
                return $range['amount'];
            }
        }

        return null;
    }

    /**
     * @param  array{ranges?: array<int, array{min_amount: int, max_amount?: int, amount: int}>}  $config
     */
    private function calculatePriceRate(array $config, Cart $cart): ?int
    {
        $subtotal = $cart->lines()->sum('line_subtotal_amount');

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            if ($subtotal >= $range['min_amount']) {
                if (! isset($range['max_amount']) || $subtotal <= $range['max_amount']) {
                    return $range['amount'];
                }
            }
        }

        return null;
    }

    public function getTotalShippingWeight(Cart $cart): int
    {
        $totalWeight = 0;
        $lines = $cart->lines()->with('variant')->get();

        foreach ($lines as $line) {
            if ($line->variant->requires_shipping) {
                $totalWeight += ($line->variant->weight_g ?? 0) * $line->quantity;
            }
        }

        return $totalWeight;
    }
}
