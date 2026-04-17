<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class ShippingCalculator
{
    /**
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $country = (string) ($address['country'] ?? '');
        $provinceCode = $address['province_code'] ?? null;
        $regionKey = $provinceCode !== null && $provinceCode !== ''
            ? $country.'-'.$provinceCode
            : null;

        $zones = ShippingZone::query()
            ->where('store_id', $store->id)
            ->get()
            ->filter(function (ShippingZone $zone) use ($country, $regionKey): bool {
                $countries = $zone->countries_json ?? [];
                $regions = $zone->regions_json ?? [];

                if ($country !== '' && in_array($country, $countries, true)) {
                    return true;
                }

                return $regionKey !== null && in_array($regionKey, $regions, true);
            });

        if ($zones->isEmpty()) {
            return new Collection;
        }

        return ShippingRate::query()
            ->whereIn('zone_id', $zones->pluck('id')->all())
            ->where('is_active', true)
            ->get();
    }

    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        $lines = $cart->relationLoaded('lines') ? $cart->lines : $cart->lines()->with('variant')->get();

        $requiresShipping = $lines->contains(function ($line): bool {
            $variant = $line->variant;

            return $variant !== null && (bool) $variant->requires_shipping;
        });

        if (! $requiresShipping) {
            return 0;
        }

        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($config['amount'] ?? 0),
            ShippingRateType::Weight => $this->weightRate($config, $lines),
            ShippingRateType::Price => $this->priceRate($config, $lines),
            ShippingRateType::Carrier => (int) ($config['fallback_amount'] ?? 0),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  iterable<int, \App\Models\CartLine>  $lines
     */
    private function weightRate(array $config, iterable $lines): int
    {
        $totalWeight = 0;
        foreach ($lines as $line) {
            $variant = $line->variant;
            if ($variant === null || ! $variant->requires_shipping) {
                continue;
            }
            $totalWeight += (int) ($variant->weight_g ?? 0) * (int) $line->quantity;
        }

        foreach ($config['ranges'] ?? [] as $range) {
            $min = (int) ($range['min_g'] ?? 0);
            $max = (int) ($range['max_g'] ?? PHP_INT_MAX);
            if ($totalWeight >= $min && $totalWeight <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  iterable<int, \App\Models\CartLine>  $lines
     */
    private function priceRate(array $config, iterable $lines): int
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += (int) $line->line_subtotal_amount;
        }

        foreach ($config['ranges'] ?? [] as $range) {
            $min = (int) ($range['min_amount'] ?? 0);
            $max = (int) ($range['max_amount'] ?? PHP_INT_MAX);
            if ($subtotal >= $min && $subtotal <= $max) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return 0;
    }
}
