<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\ValueObjects\ShippingRateQuote;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zone = $this->matchingZone($store, $address);

        if ($zone === null) {
            return collect();
        }

        return $zone->rates()
            ->where('is_active', true)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRateQuote>
     */
    public function getAvailableRateQuotes(Store $store, Cart $cart, array $address): Collection
    {
        return $this->getAvailableRates($store, $address)
            ->map(function (ShippingRate $rate) use ($cart): ?ShippingRateQuote {
                $amount = $this->calculate($rate, $cart);

                return $amount === null ? null : new ShippingRateQuote($rate, $amount, $cart->currency);
            })
            ->filter()
            ->values();
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($config['amount'] ?? 0),
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $cart),
            ShippingRateType::Carrier => (int) ($config['amount'] ?? 999),
        };
    }

    public function requiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.variant');

        return $cart->lines->contains(
            fn ($line): bool => (bool) $line->variant?->requires_shipping,
        );
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function matchingZone(Store $store, array $address): ?ShippingZone
    {
        $country = $address['country_code'] ?? $address['country'] ?? null;
        $region = $address['province_code'] ?? null;
        $bestZone = null;
        $bestSpecificity = -1;

        if (! is_string($country) || $country === '') {
            return null;
        }

        ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->get()
            ->each(function (ShippingZone $zone) use ($country, $region, &$bestZone, &$bestSpecificity): void {
                $countries = $zone->countries_json ?? [];
                $regions = $zone->regions_json ?? [];

                if (! in_array($country, $countries, true)) {
                    return;
                }

                $specificity = in_array($region, $regions, true) ? 2 : 1;

                if ($specificity > $bestSpecificity) {
                    $bestZone = $zone;
                    $bestSpecificity = $specificity;
                }
            });

        return $bestZone;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $cart->loadMissing('lines.variant');

        $totalWeight = (int) $cart->lines->sum(function ($line): int {
            if (! $line->variant?->requires_shipping) {
                return 0;
            }

            return (int) $line->variant->weight_g * $line->quantity;
        });

        foreach ($config['ranges'] ?? [] as $range) {
            if ($totalWeight < ($range['min_g'] ?? 0)) {
                continue;
            }

            if (isset($range['max_g']) && $totalWeight > $range['max_g']) {
                continue;
            }

            return (int) $range['amount'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculatePriceRate(array $config, Cart $cart): ?int
    {
        $cart->loadMissing('lines');
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

        foreach ($config['ranges'] ?? [] as $range) {
            if ($subtotal < ($range['min_amount'] ?? 0)) {
                continue;
            }

            if (isset($range['max_amount']) && $subtotal > $range['max_amount']) {
                continue;
            }

            return (int) $range['amount'];
        }

        return null;
    }
}
