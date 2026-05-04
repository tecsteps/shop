<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
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

        if (! $zone instanceof ShippingZone) {
            return collect();
        }

        return ShippingRate::withoutGlobalScopes()
            ->where('zone_id', $zone->getKey())
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function matchingZone(Store $store, array $address): ?ShippingZone
    {
        $country = strtoupper((string) (data_get($address, 'country_code') ?: data_get($address, 'country')));
        $region = strtoupper((string) data_get($address, 'province_code'));
        $bestZone = null;
        $bestSpecificity = -1;

        ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->orderBy('id')
            ->get()
            ->each(function (ShippingZone $zone) use ($country, $region, &$bestZone, &$bestSpecificity): void {
                $countries = collect($zone->countries_json)->map(fn (string $code): string => strtoupper($code));
                $regions = collect($zone->regions_json)->map(fn (string $code): string => strtoupper($code));

                if (! $countries->contains($country)) {
                    return;
                }

                $specificity = $regions->contains($region) ? 2 : 1;

                if ($specificity > $bestSpecificity) {
                    $bestZone = $zone;
                    $bestSpecificity = $specificity;
                }
            });

        return $bestZone;
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        if (! $this->requiresShipping($cart)) {
            return 0;
        }

        return match ($rate->type) {
            ShippingRateType::Flat => (int) data_get($rate->config_json, 'amount', 0),
            ShippingRateType::Weight => $this->weightRate($rate, $cart),
            ShippingRateType::Price => $this->priceRate($rate, $cart),
            ShippingRateType::Carrier => (int) data_get($rate->config_json, 'amount', 1299),
        };
    }

    public function requiresShipping(Cart $cart): bool
    {
        return $this->physicalLines($cart)->isNotEmpty();
    }

    private function weightRate(ShippingRate $rate, Cart $cart): ?int
    {
        $totalWeight = $this->physicalLines($cart)
            ->sum(function (CartLine $line): int {
                $variant = $this->variant($line);

                return ($variant?->weight_g ?? 0) * $line->quantity;
            });

        foreach (data_get($rate->config_json, 'ranges', []) as $range) {
            $minimum = (int) data_get($range, 'min_g', 0);
            $maximum = data_get($range, 'max_g');

            if ($totalWeight >= $minimum && ($maximum === null || $totalWeight <= (int) $maximum)) {
                return (int) data_get($range, 'amount', 0);
            }
        }

        return null;
    }

    private function priceRate(ShippingRate $rate, Cart $cart): ?int
    {
        $subtotal = CartLine::withoutGlobalScopes()
            ->where('cart_id', $cart->getKey())
            ->sum('line_subtotal_amount');

        foreach (data_get($rate->config_json, 'ranges', []) as $range) {
            $minimum = (int) data_get($range, 'min_amount', 0);
            $maximum = data_get($range, 'max_amount');

            if ($subtotal >= $minimum && ($maximum === null || $subtotal <= (int) $maximum)) {
                return (int) data_get($range, 'amount', 0);
            }
        }

        return null;
    }

    /**
     * @return Collection<int, CartLine>
     */
    private function physicalLines(Cart $cart): Collection
    {
        return CartLine::withoutGlobalScopes()
            ->where('cart_id', $cart->getKey())
            ->get()
            ->filter(function (CartLine $line): bool {
                return (bool) $this->variant($line)?->requires_shipping;
            });
    }

    private function variant(CartLine $line): ?ProductVariant
    {
        return ProductVariant::withoutGlobalScopes()
            ->whereKey($line->variant_id)
            ->first();
    }
}
