<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ShippingCalculator
{
    /** @return EloquentCollection<int, ShippingRate> */
    public function ratesForCheckout(Checkout $checkout): EloquentCollection
    {
        $checkout->loadMissing('cart.lines.variant');
        $cartLines = $checkout->cart?->lines ?? collect();
        $physicalLines = $cartLines->filter(static fn ($line): bool => (bool) $line->variant?->requires_shipping);

        if ($physicalLines->isEmpty()) {
            return new EloquentCollection;
        }

        $subtotal = (int) $cartLines->sum('line_subtotal_amount');
        $totalWeight = (int) $physicalLines->sum(static fn ($line): int => (int) ($line->variant?->weight_g ?? 0) * (int) $line->quantity);
        $address = $checkout->shipping_address_json ?? [];

        return $this->ratesForAddress((int) $checkout->store_id, $address, $subtotal, $totalWeight);
    }

    /** @param array<string, mixed> $address
     * @return EloquentCollection<int, ShippingRate>
     */
    public function ratesForAddress(int $storeId, array $address, int $subtotal = 0, int $totalWeight = 0): EloquentCollection
    {
        $countryCode = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $provinceCode = strtoupper((string) ($address['province_code'] ?? $address['state'] ?? ''));
        $zone = $countryCode === '' ? null : $this->matchingZone($storeId, $countryCode, $provinceCode);

        if ($zone === null) {
            return new EloquentCollection;
        }

        return $zone->rates
            ->filter(function (ShippingRate $rate) use ($subtotal, $totalWeight): bool {
                if (! $this->isAvailable($rate, $subtotal)) {
                    return false;
                }

                $amount = $this->amountForRate($rate, $subtotal, $totalWeight);

                if ($amount === null) {
                    return false;
                }

                $rate->setAttribute('price_amount', $amount);

                return true;
            })
            ->values();
    }

    public function matchingZone(int $storeId, string $countryCode, string $provinceCode): ?ShippingZone
    {
        $zones = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->with(['rates' => fn ($rates) => $rates->orderBy('id')])
            ->orderBy('id')
            ->get();
        $bestZone = null;
        $bestSpecificity = -1;

        foreach ($zones as $zone) {
            $countries = array_map('strtoupper', $zone->countries ?? []);

            if (! in_array($countryCode, $countries, true)) {
                continue;
            }

            $regions = array_map('strtoupper', $zone->regions ?? []);
            $regionMatches = $provinceCode !== '' && array_intersect($regions, [$provinceCode, $countryCode.'-'.$provinceCode]) !== [];
            $specificity = $regionMatches ? 2 : 1;

            if ($specificity > $bestSpecificity) {
                $bestZone = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $bestZone;
    }

    private function isAvailable(ShippingRate $rate, int $subtotal): bool
    {
        return $rate->is_active
            && ($rate->min_order_amount === null || $subtotal >= (int) $rate->min_order_amount)
            && ($rate->max_order_amount === null || $subtotal <= (int) $rate->max_order_amount);
    }

    private function amountForRate(ShippingRate $rate, int $subtotal, int $totalWeight): ?int
    {
        $config = $rate->config_json ?? [];

        if ($rate->type === 'flat' || $rate->type === 'carrier') {
            return (int) ($config['price_amount'] ?? $config['amount'] ?? $rate->price_amount ?? 0);
        }

        $tiers = $config['tiers'] ?? $config['ranges'] ?? [];

        foreach ($tiers as $tier) {
            if (! is_array($tier)) {
                continue;
            }

            if ($rate->type === 'weight') {
                $minimum = (int) ($tier['min_weight_g'] ?? $tier['min_g'] ?? 0);
                $maximum = $tier['max_weight_g'] ?? $tier['max_g'] ?? null;
                $value = $totalWeight;
            } elseif ($rate->type === 'price') {
                $minimum = (int) ($tier['min_order_amount'] ?? $tier['min_amount'] ?? 0);
                $maximum = $tier['max_order_amount'] ?? $tier['max_amount'] ?? null;
                $value = $subtotal;
            } else {
                return null;
            }

            if ($value >= $minimum && ($maximum === null || $value <= (int) $maximum)) {
                return (int) ($tier['price_amount'] ?? $tier['amount'] ?? 0);
            }
        }

        return null;
    }
}
