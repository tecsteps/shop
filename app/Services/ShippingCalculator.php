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
     * Get available shipping rates for the given store and address.
     *
     * @param  array{country: string, province_code?: string}  $address
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

    /**
     * Calculate the shipping cost for a given rate and cart.
     */
    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type->value ?? $rate->type) {
            'flat' => $this->calculateFlatRate($config),
            'weight' => $this->calculateWeightRate($config, $cart),
            'price' => $this->calculatePriceRate($config, $cart),
            'carrier' => $this->calculateCarrierRate($config),
            default => null,
        };
    }

    /**
     * Find the best matching shipping zone for the given address.
     *
     * @param  array{country: string, province_code?: string}  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        $countryCode = $address['country'] ?? '';
        $provinceCode = $address['province_code'] ?? '';

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($countryCode, $countries, true);

            if (! $countryMatch) {
                continue;
            }

            $regionMatch = ! empty($provinceCode) && in_array($provinceCode, $regions, true);

            if ($countryMatch && $regionMatch) {
                $specificity = 2;
            } else {
                $specificity = 1;
            }

            if ($specificity > $bestSpecificity || ($specificity === $bestSpecificity && ($bestMatch === null || $zone->id < $bestMatch->id))) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate flat rate shipping cost.
     *
     * @param  array{amount?: int}  $config
     */
    private function calculateFlatRate(array $config): int
    {
        return $config['amount'] ?? 0;
    }

    /**
     * Calculate weight-based shipping cost.
     *
     * @param  array{ranges?: array<array{min_g: int, max_g: int, amount: int}>}  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $cart->load('lines.variant');

        $totalWeight = 0;
        foreach ($cart->lines as $line) {
            if ($line->variant && $line->variant->requires_shipping) {
                $totalWeight += ($line->variant->weight_grams ?? 0) * $line->quantity;
            }
        }

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            if ($totalWeight >= $range['min_g'] && $totalWeight <= $range['max_g']) {
                return $range['amount'];
            }
        }

        return null;
    }

    /**
     * Calculate price-based shipping cost.
     *
     * @param  array{ranges?: array<array{min_amount: int, max_amount?: int, amount: int}>}  $config
     */
    private function calculatePriceRate(array $config, Cart $cart): ?int
    {
        $cart->load('lines');
        $cartSubtotal = $cart->lines->sum('line_subtotal_amount');

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            if ($cartSubtotal >= $range['min_amount']) {
                if (! isset($range['max_amount']) || $cartSubtotal <= $range['max_amount']) {
                    return $range['amount'];
                }
            }
        }

        return null;
    }

    /**
     * Calculate carrier-based shipping cost (stub).
     *
     * @param  array{carrier?: string, service?: string}  $config
     */
    private function calculateCarrierRate(array $config): int
    {
        return 999;
    }
}
