<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\ValueObjects\ShippingRateOption;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $zones = ShippingZone::where('store_id', $store->id)->get();
        $country = $address['country_code'] ?? $address['country'] ?? null;
        $province = $address['province_code'] ?? null;

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = $country !== null && in_array($country, $countries, true);
            $regionMatch = $province !== null && in_array($province, $regions, true);

            if (! $countryMatch) {
                continue;
            }

            $specificity = $regionMatch ? 2 : 1;

            if ($specificity > $bestSpecificity) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            } elseif ($specificity === $bestSpecificity && $bestMatch && $zone->id < $bestMatch->id) {
                $bestMatch = $zone;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRateOption>
     */
    public function getAvailableRates(Store $store, array $address, Cart $cart): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        if (! $zone) {
            return collect();
        }

        return $zone->rates()->where('is_active', true)->get()
            ->map(fn (ShippingRate $rate) => new ShippingRateOption(
                $rate->id,
                $rate->name,
                $this->calculate($rate, $cart),
                $rate->type,
            ))
            ->values();
    }

    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            'flat' => (int) ($config['amount'] ?? 0),
            'weight' => $this->calculateWeight($config, $cart),
            'price' => $this->calculatePrice($config, $this->cartSubtotal($cart)),
            'carrier' => 0,
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculateWeight(array $config, Cart $cart): int
    {
        $cart->loadMissing('lines.variant');
        $totalWeight = 0;

        foreach ($cart->lines as $line) {
            if ($line->variant?->requires_shipping) {
                $totalWeight += ($line->variant->weight_g ?? 0) * $line->quantity;
            }
        }

        foreach ($config['ranges'] ?? [] as $range) {
            $min = (int) ($range['min_g'] ?? 0);
            $max = isset($range['max_g']) ? (int) $range['max_g'] : null;

            if ($totalWeight >= $min && ($max === null || $totalWeight <= $max)) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculatePrice(array $config, int $subtotal): int
    {
        foreach ($config['ranges'] ?? [] as $range) {
            $min = (int) ($range['min_amount'] ?? 0);
            $max = isset($range['max_amount']) ? (int) $range['max_amount'] : null;

            if ($subtotal >= $min && ($max === null || $subtotal <= $max)) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return 0;
    }

    private function cartSubtotal(Cart $cart): int
    {
        $cart->loadMissing('lines');

        return $cart->lines->sum(fn ($line) => $line->unit_price_amount * $line->quantity);
    }
}
