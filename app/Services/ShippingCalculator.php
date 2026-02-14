<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Exceptions\InvalidCheckoutStateException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\ValueObjects\ShippingRateQuote;
use Illuminate\Support\Collection;

final class ShippingCalculator
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $countryCode = $this->normalizeCode($address['country_code'] ?? $address['country'] ?? null);
        $regionCode = $this->normalizeCode($address['province_code'] ?? $address['region_code'] ?? $address['province'] ?? null);

        if ($countryCode === null) {
            return null;
        }

        /** @var Collection<int, ShippingZone> $zones */
        $zones = ShippingZone::query()
            ->where('store_id', '=', (int) $store->id)
            ->orderBy('id')
            ->get();

        $bestMatch = null;
        $bestSpecificity = -1;

        /** @var ShippingZone $zone */
        foreach ($zones as $zone) {
            $countries = $this->normalizeCodeList($zone->countries_json);
            $regions = $this->normalizeCodeList($zone->regions_json);

            $countryMatch = in_array($countryCode, $countries, true);
            $regionMatch = $regionCode !== null && in_array($regionCode, $regions, true);

            if (! $countryMatch) {
                continue;
            }

            $specificity = $regionMatch ? 2 : 1;

            if ($specificity > $bestSpecificity) {
                $bestMatch = $zone;
                $bestSpecificity = $specificity;

                continue;
            }

            if ($specificity === $bestSpecificity && $bestMatch !== null && (int) $zone->id < (int) $bestMatch->id) {
                $bestMatch = $zone;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRateQuote>
     */
    public function getAvailableRates(Store $store, array $address, Cart $cart): Collection
    {
        if (! $this->cartRequiresShipping($cart)) {
            return collect();
        }

        $zone = $this->getMatchingZone($store, $address);

        if ($zone === null) {
            return collect();
        }

        /** @var Collection<int, ShippingRate> $rates */
        $rates = ShippingRate::query()
            ->where('zone_id', '=', (int) $zone->id)
            ->where('is_active', '=', true)
            ->orderBy('id')
            ->get();

        $quotes = [];

        /** @var ShippingRate $rate */
        foreach ($rates as $rate) {
            $amount = $this->calculate($rate, $cart);

            if ($amount === null) {
                continue;
            }

            $quotes[] = new ShippingRateQuote(
                rateId: (int) $rate->id,
                name: (string) $rate->name,
                type: $rate->type,
                amount: $amount,
            );
        }

        return collect($quotes);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function selectActiveRateByZone(Store $store, array $address, Cart $cart, int $shippingRateId, int $checkoutId = 0): ShippingRateQuote
    {
        $quote = $this->getAvailableRates($store, $address, $cart)
            ->first(static fn (ShippingRateQuote $candidate): bool => $candidate->rateId === $shippingRateId);

        if ($quote === null) {
            throw InvalidCheckoutStateException::invalidShippingMethod($checkoutId, $shippingRateId);
        }

        return $quote;
    }

    public function cartRequiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.variant');

        /** @var CartLine $line */
        foreach ($cart->lines as $line) {
            if ((bool) ($line->variant?->requires_shipping ?? false)) {
                return true;
            }
        }

        return false;
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $cart->loadMissing('lines.variant');
        $config = is_array($rate->config_json) ? $rate->config_json : [];

        return match ($rate->type) {
            ShippingRateType::Flat => $this->asInt($config['amount'] ?? 0),
            ShippingRateType::Weight => $this->calculateWeightRate($config, $cart),
            ShippingRateType::Price => $this->calculatePriceRate($config, $this->cartSubtotal($cart)),
            ShippingRateType::Carrier => $this->asInt($config['amount'] ?? 0),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculateWeightRate(array $config, Cart $cart): ?int
    {
        $ranges = is_array($config['ranges'] ?? null) ? $config['ranges'] : [];

        if ($ranges === []) {
            return null;
        }

        $weight = 0;

        /** @var CartLine $line */
        foreach ($cart->lines as $line) {
            if (! (bool) ($line->variant?->requires_shipping ?? false)) {
                continue;
            }

            $lineWeight = $this->asInt($line->variant?->weight_g);
            $weight += $lineWeight * (int) $line->quantity;
        }

        foreach ($ranges as $range) {
            if (! is_array($range)) {
                continue;
            }

            $min = $this->asInt($range['min_g'] ?? 0);
            $max = $range['max_g'] ?? null;
            $maxValue = is_numeric($max) ? (int) $max : null;

            if ($weight < $min) {
                continue;
            }

            if ($maxValue !== null && $weight > $maxValue) {
                continue;
            }

            return $this->asInt($range['amount'] ?? 0);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function calculatePriceRate(array $config, int $cartSubtotal): ?int
    {
        $ranges = is_array($config['ranges'] ?? null) ? $config['ranges'] : [];

        if ($ranges === []) {
            return null;
        }

        foreach ($ranges as $range) {
            if (! is_array($range)) {
                continue;
            }

            $min = $this->asInt($range['min_amount'] ?? 0);
            $max = $range['max_amount'] ?? null;
            $maxValue = is_numeric($max) ? (int) $max : null;

            if ($cartSubtotal < $min) {
                continue;
            }

            if ($maxValue !== null && $cartSubtotal > $maxValue) {
                continue;
            }

            return $this->asInt($range['amount'] ?? 0);
        }

        return null;
    }

    private function cartSubtotal(Cart $cart): int
    {
        $subtotal = 0;

        /** @var CartLine $line */
        foreach ($cart->lines as $line) {
            $subtotal += (int) $line->unit_price_amount * (int) $line->quantity;
        }

        return $subtotal;
    }

    private function normalizeCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return list<string>
     */
    private function normalizeCodeList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            $code = $this->normalizeCode($value);

            if ($code === null) {
                continue;
            }

            $normalized[] = $code;
        }

        return array_values(array_unique($normalized));
    }

    private function asInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || is_string($value)) {
            return (int) $value;
        }

        return 0;
    }
}
