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
        $zone = $this->getMatchingZone($store, $address);

        if (! $zone) {
            return collect();
        }

        return $zone->rates()->where('is_active', true)->get();
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json;

        return match ($rate->type) {
            ShippingRateType::Flat => $config['amount'] ?? 0,
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $cart),
            ShippingRateType::Carrier => $config['amount'] ?? 999,
        };
    }

    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

        $countryCode = $address['country'] ?? $address['country_code'] ?? null;
        $regionCode = $address['province_code'] ?? $address['province'] ?? null;

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($countryCode, $countries);
            $regionMatch = $regionCode && in_array($regionCode, $regions);

            if ($countryMatch && $regionMatch) {
                $specificity = 2;
            } elseif ($countryMatch) {
                $specificity = 1;
            } else {
                continue;
            }

            if ($specificity > $bestSpecificity || ($specificity === $bestSpecificity && $zone->id < $bestMatch->id)) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $bestMatch;
    }

    protected function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $totalWeight = 0;

        foreach ($cart->lines()->with('variant')->get() as $line) {
            if ($line->variant->requires_shipping) {
                $totalWeight += ($line->variant->weight_g ?? 0) * $line->quantity;
            }
        }

        foreach ($config['ranges'] ?? [] as $range) {
            if ($totalWeight >= $range['min_g'] && $totalWeight <= $range['max_g']) {
                return $range['amount'];
            }
        }

        return null;
    }

    protected function calculatePriceRate(array $config, Cart $cart): ?int
    {
        $cartSubtotal = $cart->lines->sum('line_subtotal_amount');

        foreach ($config['ranges'] ?? [] as $range) {
            $minAmount = $range['min_amount'];
            $maxAmount = $range['max_amount'] ?? null;

            if ($cartSubtotal >= $minAmount && ($maxAmount === null || $cartSubtotal <= $maxAmount)) {
                return $range['amount'];
            }
        }

        return null;
    }
}
