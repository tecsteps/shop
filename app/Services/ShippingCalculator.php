<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Support\Collection;
use RuntimeException;

class ShippingCalculator
{
    /**
     * All active rates from zones matching the address, most specific zone
     * first (region match beats country-only match, then lowest zone id).
     *
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        return $this->matchingZones($store, $address)
            ->flatMap(fn (ShippingZone $zone) => $zone->rates()->where('is_active', true)->get())
            ->values();
    }

    /**
     * Whether the selected rate's zone matches the address.
     *
     * @param  array<string, mixed>  $address
     */
    public function rateMatchesAddress(ShippingRate $rate, Store $store, array $address): bool
    {
        return $rate->is_active && $this->matchingZones($store, $address)
            ->contains(fn (ShippingZone $zone): bool => $zone->getKey() === $rate->zone_id);
    }

    /**
     * Calculate the shipping cost for the rate against the cart contents.
     * Returns 0 when nothing in the cart requires shipping.
     */
    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        if (! $cart->requiresShipping()) {
            return 0;
        }

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($rate->config_json['amount'] ?? 0),
            ShippingRateType::Weight => $this->matchRange(
                $rate->config_json['ranges'] ?? [],
                $cart->totalWeightGrams(),
                'min_g',
                'max_g',
            ),
            ShippingRateType::Price => $this->matchRange(
                $rate->config_json['ranges'] ?? [],
                $cart->subtotalAmount(),
                'min_amount',
                'max_amount',
            ),
            ShippingRateType::Carrier => throw new RuntimeException('Carrier-calculated rates are not implemented.'),
        };
    }

    /**
     * Zones matching the address, ordered by specificity (country + region
     * before country-only) with lowest zone id as the tie-breaker.
     *
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingZone>
     */
    protected function matchingZones(Store $store, array $address): Collection
    {
        $countryCode = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $provinceCode = (string) ($address['province_code'] ?? '');

        return ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get()
            ->map(function (ShippingZone $zone) use ($countryCode, $provinceCode): ?array {
                $countryMatch = in_array($countryCode, $zone->countries_json ?? [], true);
                $regionMatch = $provinceCode !== '' && in_array($provinceCode, $zone->regions_json ?? [], true);

                if (! $countryMatch && ! $regionMatch) {
                    return null;
                }

                return ['zone' => $zone, 'specificity' => $countryMatch && $regionMatch ? 2 : 1];
            })
            ->filter()
            ->sort(fn (array $a, array $b): int => ($b['specificity'] <=> $a['specificity'])
                ?: ($a['zone']->getKey() <=> $b['zone']->getKey()))
            ->map(fn (array $match): ShippingZone => $match['zone'])
            ->values();
    }

    /**
     * Find the matching tier amount for a value. A range without an upper
     * bound matches everything above its minimum.
     *
     * @param  list<array<string, int>>  $ranges
     */
    protected function matchRange(array $ranges, int $value, string $minKey, string $maxKey): int
    {
        foreach ($ranges as $range) {
            $min = (int) ($range[$minKey] ?? 0);
            $max = $range[$maxKey] ?? null;

            if ($value >= $min && ($max === null || $value <= (int) $max)) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        throw new RuntimeException('No shipping range matches the cart for this rate.');
    }
}
