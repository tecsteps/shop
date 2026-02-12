<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;

class ShippingService
{
    public function requiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.variant');

        foreach ($cart->lines as $line) {
            if ($line->variant->requires_shipping) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @return array<int, array<string, mixed>>
     */
    public function availableRates(Store $store, Cart $cart, ?array $address): array
    {
        if (! $this->requiresShipping($cart)) {
            return [];
        }

        if (! $address) {
            return [];
        }

        $zone = $this->matchingZone($store, $address);

        if (! $zone) {
            return [];
        }

        $rates = $zone->rates()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $result = [];

        foreach ($rates as $rate) {
            $amount = $this->calculateRateAmount($rate, $cart);

            if ($amount === null) {
                continue;
            }

            $result[] = [
                'id' => $rate->id,
                'name' => $rate->name,
                'type' => $rate->type->value,
                'price_amount' => $amount,
                'currency' => $cart->currency,
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function validateRateSelection(Store $store, Cart $cart, array $address, int $shippingRateId): ShippingRate
    {
        $zone = $this->matchingZone($store, $address);

        if (! $zone) {
            throw new \InvalidArgumentException('Address is not serviceable.');
        }

        /** @var ShippingRate|null $rate */
        $rate = $zone->rates()
            ->where('is_active', true)
            ->whereKey($shippingRateId)
            ->first();

        if (! $rate) {
            throw new \InvalidArgumentException('Invalid shipping method.');
        }

        if ($this->calculateRateAmount($rate, $cart) === null) {
            throw new \InvalidArgumentException('Shipping method is not available for this cart.');
        }

        return $rate;
    }

    /**
     * @param  array<string, mixed>|null  $address
     */
    public function calculateShippingAmount(Cart $cart, ?ShippingRate $shippingRate, ?Discount $discount = null, ?array $address = null): int
    {
        if (! $this->requiresShipping($cart)) {
            return 0;
        }

        if ($discount && $discount->value_type === DiscountValueType::FreeShipping) {
            return 0;
        }

        if (! $shippingRate) {
            return 0;
        }

        if ($address) {
            $zone = $this->matchingZone($cart->store, $address);

            if (! $zone || $zone->id !== $shippingRate->zone_id) {
                return 0;
            }
        }

        return (int) ($this->calculateRateAmount($shippingRate, $cart) ?? 0);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function matchingZone(Store $store, array $address): ?ShippingZone
    {
        $country = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $region = strtoupper((string) ($address['province_code'] ?? ''));

        if ($country === '') {
            return null;
        }

        $zones = ShippingZone::query()
            ->where('store_id', $store->id)
            ->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = array_map('strtoupper', (array) $zone->countries_json);
            $regions = array_map('strtoupper', (array) $zone->regions_json);

            $countryMatch = in_array($country, $countries, true);
            $regionMatch = $region !== '' && in_array($region, $regions, true);

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

                continue;
            }

            if ($specificity === $bestSpecificity && $bestMatch && $zone->id < $bestMatch->id) {
                $bestMatch = $zone;
            }
        }

        return $bestMatch;
    }

    private function calculateRateAmount(ShippingRate $rate, Cart $cart): ?int
    {
        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($rate->config_json['amount'] ?? 0),
            ShippingRateType::Weight => $this->calculateWeightRate($rate, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($rate, $cart),
            ShippingRateType::Carrier => (int) ($rate->config_json['amount'] ?? 0),
        };
    }

    private function calculateWeightRate(ShippingRate $rate, Cart $cart): ?int
    {
        $ranges = (array) ($rate->config_json['ranges'] ?? []);
        $totalWeight = 0;

        $cart->loadMissing('lines.variant');

        foreach ($cart->lines as $line) {
            if (! $line->variant->requires_shipping) {
                continue;
            }

            $totalWeight += (int) ($line->variant->weight_g ?? 0) * $line->quantity;
        }

        foreach ($ranges as $range) {
            $min = (int) ($range['min_g'] ?? 0);
            $max = array_key_exists('max_g', $range) ? (int) $range['max_g'] : null;

            if ($totalWeight < $min) {
                continue;
            }

            if ($max !== null && $totalWeight > $max) {
                continue;
            }

            return (int) ($range['amount'] ?? 0);
        }

        return null;
    }

    private function calculatePriceRate(ShippingRate $rate, Cart $cart): ?int
    {
        $ranges = (array) ($rate->config_json['ranges'] ?? []);
        $subtotal = (int) $cart->lines->sum('line_total_amount');

        foreach ($ranges as $range) {
            $min = (int) ($range['min_amount'] ?? 0);
            $max = array_key_exists('max_amount', $range) ? (int) $range['max_amount'] : null;

            if ($subtotal < $min) {
                continue;
            }

            if ($max !== null && $subtotal > $max) {
                continue;
            }

            return (int) ($range['amount'] ?? 0);
        }

        return null;
    }
}
