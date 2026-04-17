<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\ValueObjects\ShippingRateOption;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @param  array{country: string, province_code?: string|null}  $address
     * @return Collection<int, ShippingRateOption>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        if (! $zone) {
            return collect();
        }

        $rates = $zone->rates()
            ->where('is_active', true)
            ->get();

        return $rates->map(function (ShippingRate $rate) {
            return new ShippingRateOption(
                id: $rate->id,
                name: $rate->name,
                amount: $this->getBaseAmount($rate),
                type: $rate->type->value,
            );
        });
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json;

        return match ($rate->type) {
            ShippingRateType::Flat => $this->calculateFlat($config),
            ShippingRateType::Weight => $this->calculateWeight($config, $cart),
            ShippingRateType::Price => $this->calculatePrice($config, $cart),
            ShippingRateType::Carrier => $this->calculateCarrier($config),
        };
    }

    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $countryCode = $address['country'] ?? null;
        $provinceCode = $address['province_code'] ?? null;

        if (! $countryCode) {
            return null;
        }

        $zones = ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

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

            if ($specificity > $bestSpecificity || ($specificity === $bestSpecificity && ($bestMatch === null || $zone->id < $bestMatch->id))) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array{amount: int}  $config
     */
    protected function calculateFlat(array $config): int
    {
        return $config['amount'] ?? 0;
    }

    /**
     * @param  array{ranges: array<array{min_g: int, max_g: int, amount: int}>}  $config
     */
    protected function calculateWeight(array $config, Cart $cart): ?int
    {
        $totalWeight = 0;

        foreach ($cart->lines()->with('variant')->get() as $line) {
            if ($line->variant && $line->variant->requires_shipping) {
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

    /**
     * @param  array{ranges: array<array{min_amount: int, max_amount?: int, amount: int}>}  $config
     */
    protected function calculatePrice(array $config, Cart $cart): ?int
    {
        $subtotal = $cart->lines->sum('line_subtotal_amount');

        foreach ($config['ranges'] ?? [] as $range) {
            if ($subtotal >= $range['min_amount']) {
                if (! isset($range['max_amount']) || $subtotal <= $range['max_amount']) {
                    return $range['amount'];
                }
            }
        }

        return null;
    }

    /**
     * @param  array{carrier: string, service: string}  $config
     */
    protected function calculateCarrier(array $config): int
    {
        return 999;
    }

    protected function getBaseAmount(ShippingRate $rate): int
    {
        $config = $rate->config_json;

        return match ($rate->type) {
            ShippingRateType::Flat => $config['amount'] ?? 0,
            default => 0,
        };
    }
}
