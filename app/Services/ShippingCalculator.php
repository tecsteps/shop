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
     * @param  array<string, mixed>  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $countryCode = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $regionCode = strtoupper((string) ($address['province_code'] ?? ''));

        if ($countryCode === '') {
            return null;
        }

        $zones = ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = array_map(fn ($v): string => strtoupper((string) $v), $zone->countries_json ?? []);
            $regions = array_map(fn ($v): string => strtoupper((string) $v), $zone->regions_json ?? []);

            if (! in_array($countryCode, $countries, true)) {
                continue;
            }

            $regionKey = $countryCode.'-'.$regionCode;
            $specificity = ($regionCode !== '' && in_array($regionKey, $regions, true)) ? 2 : 1;

            if ($specificity > $bestSpecificity) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            } elseif ($bestMatch !== null && $specificity === $bestSpecificity && $zone->getKey() < $bestMatch->getKey()) {
                $bestMatch = $zone;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        if ($zone === null) {
            return collect();
        }

        return $zone->rates()->where('is_active', 1)->get();
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];
        $lines = $cart->lines()->with('variant')->get();

        $physicalLines = $lines->filter(fn ($line): bool => $line->variant && $line->variant->requires_shipping)->values();

        if ($physicalLines->isEmpty()) {
            return 0;
        }

        return match ($rate->type) {
            ShippingRateType::Flat => isset($config['amount']) ? (int) $config['amount'] : null,
            ShippingRateType::Weight => $this->calculateByWeight($config, $physicalLines),
            ShippingRateType::Price => $this->calculateByPrice($config, (int) $lines->sum('line_subtotal_amount')),
            ShippingRateType::Carrier => isset($config['amount']) ? (int) $config['amount'] : null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  Collection<int, \App\Models\CartLine>  $lines
     */
    protected function calculateByWeight(array $config, Collection $lines): ?int
    {
        $totalWeight = 0;

        foreach ($lines as $line) {
            $totalWeight += ((int) ($line->variant->weight_g ?? 0)) * $line->quantity;
        }

        foreach ($config['ranges'] ?? [] as $range) {
            $min = (int) ($range['min_g'] ?? 0);
            $max = isset($range['max_g']) ? (int) $range['max_g'] : PHP_INT_MAX;

            if ($totalWeight >= $min && $totalWeight <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function calculateByPrice(array $config, int $subtotal): ?int
    {
        foreach ($config['ranges'] ?? [] as $range) {
            $min = (int) ($range['min_amount'] ?? 0);
            $max = isset($range['max_amount']) ? (int) $range['max_amount'] : PHP_INT_MAX;

            if ($subtotal >= $min && $subtotal <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return null;
    }
}
