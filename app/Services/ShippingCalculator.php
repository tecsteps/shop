<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use BackedEnum;
use Illuminate\Support\Collection;

final class ShippingCalculator
{
    /** @param array<string, mixed> $address @return Collection<int, ShippingRate> */
    public function getAvailableRates(Store $store, array $address, ?Cart $cart = null): Collection
    {
        if ($cart !== null && ! $this->requiresShipping($cart)) {
            return collect();
        }

        $zone = $this->matchingZone($store, $address);
        if ($zone === null) {
            return collect();
        }

        return $zone->rates()->where('is_active', true)->get()
            ->filter(function (ShippingRate $rate) use ($cart): bool {
                $amount = $cart === null ? $this->configuredAmount($rate) : $this->calculate($rate, $cart);
                if ($amount === null) {
                    return false;
                }
                $rate->setAttribute('calculated_amount', $amount);

                return true;
            })->values();
    }

    /** @param array<string, mixed> $address */
    public function matchingZone(Store $store, array $address): ?ShippingZone
    {
        $country = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $region = strtoupper((string) ($address['province_code'] ?? ''));

        return ShippingZone::withoutGlobalScopes()->where('store_id', $store->id)->get()
            ->map(function (ShippingZone $zone) use ($country, $region): array {
                $countries = array_map('strtoupper', (array) $zone->countries_json);
                $regions = array_map('strtoupper', (array) $zone->regions_json);
                $countryMatch = in_array($country, $countries, true);
                $regionMatch = $region !== '' && in_array($region, $regions, true);

                return ['zone' => $zone, 'specificity' => $countryMatch ? ($regionMatch ? 2 : 1) : -1];
            })
            ->filter(fn (array $match): bool => $match['specificity'] >= 0)
            ->sort(function (array $left, array $right): int {
                return $right['specificity'] <=> $left['specificity'] ?: $left['zone']->id <=> $right['zone']->id;
            })
            ->first()['zone'] ?? null;
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        if (! $this->requiresShipping($cart)) {
            return 0;
        }

        $config = (array) $rate->config_json;

        return match ($this->value($rate->type)) {
            'flat' => (int) ($config['amount'] ?? 0),
            'weight' => $this->rangeAmount((array) ($config['ranges'] ?? []), $this->weight($cart), 'min_g', 'max_g'),
            'price' => $this->rangeAmount((array) ($config['ranges'] ?? []), (int) $cart->lines->sum('line_subtotal_amount'), 'min_amount', 'max_amount'),
            'carrier' => (int) ($config['fallback_amount'] ?? 999),
            default => null,
        };
    }

    public function requiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.variant');

        return $cart->lines->contains(fn ($line): bool => (bool) $line->variant?->requires_shipping);
    }

    private function weight(Cart $cart): int
    {
        return (int) $cart->lines->sum(fn ($line): int => $line->variant?->requires_shipping
            ? (int) ($line->variant->weight_g ?? 0) * (int) $line->quantity
            : 0);
    }

    /** @param list<array<string, mixed>> $ranges */
    private function rangeAmount(array $ranges, int $value, string $minimumKey, string $maximumKey): ?int
    {
        foreach ($ranges as $range) {
            if ($value >= (int) ($range[$minimumKey] ?? 0) && (! isset($range[$maximumKey]) || $value <= (int) $range[$maximumKey])) {
                return (int) ($range['amount'] ?? 0);
            }
        }

        return null;
    }

    private function configuredAmount(ShippingRate $rate): ?int
    {
        $config = (array) $rate->config_json;

        return $this->value($rate->type) === 'flat' ? (int) ($config['amount'] ?? 0) : 0;
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
