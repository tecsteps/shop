<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\ValueObjects\Address;
use App\ValueObjects\ShippingRateVO;
use Illuminate\Support\Collection;

/**
 * Shipping zone matching and rate calculation (spec 05 §9).
 * All amounts are integers in minor units.
 */
class ShippingCalculator
{
    /**
     * Find the best matching shipping zone for an address.
     *
     * Specificity: country + region match = 2, country-only = 1. On ties the
     * zone with the lowest ID wins. Returns null when no zone matches.
     */
    public function getMatchingZone(Store $store, Address $address): ?ShippingZone
    {
        $zones = ShippingZone::query()
            ->where('store_id', $store->id)
            ->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countryMatch = in_array($address->countryCode, $zone->countries_json ?? [], true);
            $regionMatch = $address->provinceCode !== null
                && in_array($address->provinceCode, $zone->regions_json ?? [], true);

            if ($countryMatch && $regionMatch) {
                $specificity = 2;
            } elseif ($countryMatch) {
                $specificity = 1;
            } else {
                continue;
            }

            if ($specificity > $bestSpecificity
                || ($specificity === $bestSpecificity && $bestMatch !== null && $zone->id < $bestMatch->id)) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $bestMatch;
    }

    /**
     * All active, purchasable rates for the address as calculated options.
     * Rates whose conditions (weight/price range) do not match are excluded.
     * Carts with no shippable lines have no available rates at all.
     *
     * @return Collection<int, ShippingRateVO>
     */
    public function getAvailableRates(Store $store, Address $address, Cart $cart): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        if ($zone === null || ! $cart->requiresShipping()) {
            return collect();
        }

        return $zone->rates()
            ->where('is_active', true)
            ->get()
            ->map(function (ShippingRate $rate) use ($cart): ?ShippingRateVO {
                $amount = $this->calculate($rate, $cart);

                if ($amount === null) {
                    return null;
                }

                $config = $rate->config_json ?? [];

                return new ShippingRateVO(
                    id: $rate->id,
                    name: $rate->name,
                    amount: $amount,
                    type: $rate->type,
                    estimatedDaysMin: isset($config['estimated_days_min']) ? (int) $config['estimated_days_min'] : null,
                    estimatedDaysMax: isset($config['estimated_days_max']) ? (int) $config['estimated_days_max'] : null,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * Calculate the cost of a rate for a cart; null when the rate's
     * conditions do not match (out of range / unsupported carrier stub).
     */
    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            ShippingRateType::Flat => isset($config['amount']) ? (int) $config['amount'] : null,
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $cart->subtotal()),
            ShippingRateType::Carrier => null, // carrier API integration stub
        };
    }

    /**
     * Match the cart's total shippable weight against the configured ranges.
     *
     * @param  array<string, mixed>  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $totalWeight = 0;

        foreach ($cart->lines as $line) {
            if ($line->variant?->requires_shipping) {
                $totalWeight += (int) ($line->variant->weight_g ?? 0) * $line->quantity;
            }
        }

        foreach ($config['ranges'] ?? [] as $range) {
            if ($range['min_g'] <= $totalWeight && $totalWeight <= $range['max_g']) {
                return (int) $range['amount'];
            }
        }

        return null;
    }

    /**
     * Match the cart subtotal against the configured ranges. A range without
     * max_amount is open-ended ("free shipping over X").
     *
     * @param  array<string, mixed>  $config
     */
    private function calculatePriceRate(array $config, int $cartSubtotal): ?int
    {
        foreach ($config['ranges'] ?? [] as $range) {
            if ($range['min_amount'] <= $cartSubtotal
                && (! isset($range['max_amount']) || $cartSubtotal <= $range['max_amount'])) {
                return (int) $range['amount'];
            }
        }

        return null;
    }
}
