<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;

class ShippingCalculator
{
    /**
     * @param  array{country?: string, province_code?: string}  $address
     */
    public function getMatchingZone(array $address, Store $store): ?ShippingZone
    {
        $countryCode = $address['country'] ?? null;
        $provinceCode = $address['province_code'] ?? null;

        if (! $countryCode) {
            return null;
        }

        $zones = ShippingZone::where('store_id', $store->id)->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($countryCode, $countries);

            if (! $countryMatch) {
                continue;
            }

            $regionMatch = $provinceCode && in_array($provinceCode, $regions);

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
     * Get available rates with calculated amounts for a zone and cart.
     *
     * @return list<array{id: int, name: string, amount: int, type: string}>
     */
    public function getAvailableRates(ShippingZone $zone, Cart $cart): array
    {
        $rates = $zone->rates()->where('is_active', true)->orderBy('position')->get();
        $results = [];

        foreach ($rates as $rate) {
            $amount = $this->calculateRateAmount($rate, $cart);

            if ($amount !== null) {
                $results[] = [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'amount' => $amount,
                    'type' => $rate->type->value,
                ];
            }
        }

        return $results;
    }

    public function calculateRateAmount(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => $config['amount'] ?? 0,
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $cart),
            ShippingRateType::Carrier => $config['amount'] ?? 999,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $cart->loadMissing('lines.variant');

        $totalWeight = 0;

        foreach ($cart->lines as $line) {
            if ($line->requires_shipping) {
                $totalWeight += ($line->variant?->weight_g ?? 0) * $line->quantity;
            }
        }

        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            $minG = $range['min_g'] ?? 0;
            $maxG = $range['max_g'] ?? PHP_INT_MAX;

            if ($totalWeight >= $minG && $totalWeight <= $maxG) {
                return $range['amount'] ?? 0;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculatePriceRate(array $config, Cart $cart): ?int
    {
        $subtotal = $cart->lines->sum('line_subtotal_amount');
        $ranges = $config['ranges'] ?? [];

        foreach ($ranges as $range) {
            $minAmount = $range['min_amount'] ?? 0;
            $maxAmount = $range['max_amount'] ?? null;

            if ($subtotal >= $minAmount && ($maxAmount === null || $subtotal <= $maxAmount)) {
                return $range['amount'] ?? 0;
            }
        }

        return null;
    }
}
