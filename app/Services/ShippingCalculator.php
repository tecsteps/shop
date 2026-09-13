<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\ValueObjects\ShippingRateOption;
use Illuminate\Support\Collection;

/**
 * Zone matching and rate calculation per spec 05 section 9.
 */
class ShippingCalculator
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $countryCode = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $provinceCode = strtoupper((string) ($address['province_code'] ?? ''));

        if ($countryCode === '') {
            return null;
        }

        $zones = ShippingZone::query()->where('store_id', $store->getKey())->orderBy('id')->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = array_map('strtoupper', array_map('strval', (array) ($zone->countries_json ?? [])));
            $regions = array_map('strtoupper', array_map('strval', (array) ($zone->regions_json ?? [])));

            $countryMatch = in_array($countryCode, $countries, true);
            $regionMatch = $provinceCode !== '' && in_array($provinceCode, $regions, true);

            if ($countryMatch && $regionMatch) {
                $specificity = 2;
            } elseif ($countryMatch) {
                $specificity = 1;
            } else {
                continue;
            }

            if ($specificity > $bestSpecificity) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;
            } elseif ($specificity === $bestSpecificity && $bestMatch !== null && $zone->getKey() < $bestMatch->getKey()) {
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
        if (! $this->requiresShipping($cart)) {
            return collect();
        }

        $zone = $this->getMatchingZone($store, $address);

        if ($zone === null) {
            return collect();
        }

        $rates = ShippingRate::query()
            ->where('zone_id', $zone->getKey())
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $options = collect();

        foreach ($rates as $rate) {
            $amount = $this->calculate($rate, $cart);

            if ($amount !== null) {
                $options->push(new ShippingRateOption(
                    id: $rate->getKey(),
                    name: $rate->name,
                    amount: $amount,
                    type: $rate->type->value,
                ));
            }
        }

        return $options;
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        return match ($rate->type) {
            ShippingRateType::Flat => $this->calculateFlat($rate),
            ShippingRateType::Weight => $this->calculateWeight($rate, $cart),
            ShippingRateType::Price => $this->calculatePrice($rate, $cart),
            ShippingRateType::Carrier => $this->calculateCarrier($rate),
        };
    }

    public function requiresShipping(Cart $cart): bool
    {
        return $cart->cartLines()
            ->whereHas('variant', fn ($query) => $query->where('requires_shipping', true))
            ->exists();
    }

    public function cartWeightG(Cart $cart): int
    {
        $weight = 0;

        foreach ($cart->cartLines()->with('variant')->get() as $line) {
            if ($line->variant->requires_shipping) {
                $weight += ((int) ($line->variant->weight_g ?? 0)) * $line->quantity;
            }
        }

        return $weight;
    }

    public function cartSubtotal(Cart $cart): int
    {
        return (int) $cart->cartLines()->sum('line_subtotal_amount');
    }

    protected function calculateFlat(ShippingRate $rate): int
    {
        $config = $rate->config_json ?? [];

        return (int) ($config['amount'] ?? $config['price_amount'] ?? 0);
    }

    protected function calculateWeight(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];
        $ranges = $config['ranges'] ?? $config['tiers'] ?? [];
        $totalWeight = $this->cartWeightG($cart);

        foreach ($ranges as $range) {
            $min = (int) ($range['min_g'] ?? $range['min_weight_g'] ?? 0);
            $max = $range['max_g'] ?? $range['max_weight_g'] ?? null;
            $amount = $range['amount'] ?? $range['price_amount'] ?? null;

            if ($amount === null) {
                continue;
            }

            if ($totalWeight >= $min && ($max === null || $totalWeight <= (int) $max)) {
                return (int) $amount;
            }
        }

        return null;
    }

    protected function calculatePrice(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json ?? [];
        $ranges = $config['ranges'] ?? $config['tiers'] ?? [];
        $subtotal = $this->cartSubtotal($cart);

        foreach ($ranges as $range) {
            $min = (int) ($range['min_amount'] ?? 0);
            $max = $range['max_amount'] ?? null;
            $amount = $range['amount'] ?? $range['price_amount'] ?? null;

            if ($amount === null) {
                continue;
            }

            if ($subtotal >= $min && ($max === null || $subtotal <= (int) $max)) {
                return (int) $amount;
            }
        }

        return null;
    }

    protected function calculateCarrier(ShippingRate $rate): ?int
    {
        $config = $rate->config_json ?? [];
        $amount = $config['amount'] ?? $config['price_amount'] ?? null;

        return $amount === null ? null : (int) $amount;
    }
}
