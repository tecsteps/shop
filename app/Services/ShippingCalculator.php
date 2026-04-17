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
     * Get available shipping rates for an address.
     *
     * @param  array{country?: string, province_code?: string}  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with(['rates' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $matchingZones = $this->matchZones($zones, $address);

        if ($matchingZones->isEmpty()) {
            return collect();
        }

        return $matchingZones->flatMap(fn (ShippingZone $zone) => $zone->rates);
    }

    /**
     * Calculate shipping cost for a given rate and cart.
     */
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

    /**
     * Check if the cart requires shipping.
     */
    public function cartRequiresShipping(Cart $cart): bool
    {
        $cart->load('lines.variant');

        foreach ($cart->lines as $line) {
            if ($line->variant->requires_shipping) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match shipping zones by address using specificity-based matching.
     *
     * @param  Collection<int, ShippingZone>  $zones
     * @param  array{country?: string, province_code?: string}  $address
     * @return Collection<int, ShippingZone>
     */
    private function matchZones(Collection $zones, array $address): Collection
    {
        $country = $address['country'] ?? '';
        $region = $address['province_code'] ?? '';

        $matched = [];

        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            $regions = $zone->regions_json ?? [];

            $countryMatch = in_array($country, $countries);
            $regionMatch = ! empty($region) && in_array($region, $regions);

            if ($countryMatch && $regionMatch) {
                $matched[] = ['zone' => $zone, 'specificity' => 2];
            } elseif ($countryMatch) {
                $matched[] = ['zone' => $zone, 'specificity' => 1];
            }
        }

        if (empty($matched)) {
            return collect();
        }

        $maxSpecificity = max(array_column($matched, 'specificity'));

        return collect($matched)
            ->filter(fn ($m) => $m['specificity'] === $maxSpecificity)
            ->map(fn ($m) => $m['zone'])
            ->values();
    }

    /**
     * @param  array{amount?: int}  $config
     */
    private function calculateFlat(array $config): int
    {
        return $config['amount'] ?? 0;
    }

    /**
     * @param  array{ranges?: list<array{min_g: int, max_g: int, amount: int}>}  $config
     */
    private function calculateWeight(array $config, Cart $cart): ?int
    {
        $cart->load('lines.variant');

        $totalWeight = 0;

        foreach ($cart->lines as $line) {
            if ($line->variant->requires_shipping) {
                $totalWeight += ($line->variant->weight_g ?? 0) * $line->quantity;
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
     * @param  array{ranges?: list<array{min_amount: int, max_amount?: int, amount: int}>}  $config
     */
    private function calculatePrice(array $config, Cart $cart): ?int
    {
        $subtotal = $cart->lines->sum('line_subtotal_amount');
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

    /**
     * Carrier-calculated rate stub.
     *
     * @param  array<string, mixed>  $config
     */
    private function calculateCarrier(array $config): int
    {
        return 999;
    }
}
