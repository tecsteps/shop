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
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => $config['amount'] ?? 0,
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $cart),
            ShippingRateType::Carrier => $config['amount'] ?? 999,
        };
    }

    /**
     * @param  array{country?: string, province_code?: string}  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $zones = ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        $country = $address['country'] ?? '';
        $provinceCode = $address['province_code'] ?? '';

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($country, $countries);
            if (! $countryMatch) {
                continue;
            }

            $regionMatch = ! empty($provinceCode) && in_array($provinceCode, $regions);

            if ($countryMatch && $regionMatch) {
                $specificity = 2;
            } elseif ($countryMatch) {
                $specificity = 1;
            } else {
                continue;
            }

            if ($specificity > $bestSpecificity || ($specificity === $bestSpecificity && ($bestMatch === null || $zone->id < $bestMatch->id))) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array{ranges?: list<array{min_g: int, max_g: int, amount: int}>}  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $totalWeight = 0;
        $cart->load('lines.variant');

        foreach ($cart->lines as $line) {
            if ($line->variant->requires_shipping) {
                $totalWeight += ($line->variant->weight_g ?? 0) * $line->quantity;
            }
        }

        $ranges = $config['ranges'] ?? [];
        foreach ($ranges as $range) {
            if ($range['min_g'] <= $totalWeight && $totalWeight <= $range['max_g']) {
                return $range['amount'];
            }
        }

        return null;
    }

    /**
     * @param  array{ranges?: list<array{min_amount: int, max_amount?: int, amount: int}>}  $config
     */
    private function calculatePriceRate(array $config, Cart $cart): ?int
    {
        $cartSubtotal = $cart->lines->sum('line_subtotal_amount');

        $ranges = $config['ranges'] ?? [];
        foreach ($ranges as $range) {
            if ($range['min_amount'] <= $cartSubtotal) {
                if (! isset($range['max_amount']) || $cartSubtotal <= $range['max_amount']) {
                    return $range['amount'];
                }
            }
        }

        return null;
    }
}
