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
    /** @param array<string, mixed> $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $zone = $this->getMatchingZone($store, $address);

        return $zone?->rates()->where('is_active', true)->get() ?? collect();
    }

    /** @param array<string, mixed> $address */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $country = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $region = strtoupper((string) ($address['province_code'] ?? ''));

        return ShippingZone::query()
            ->where('store_id', $store->id)
            ->get()
            ->map(function (ShippingZone $zone) use ($country, $region): array {
                $countryMatch = in_array($country, $zone->countries_json, true);
                $regionMatch = $region !== '' && in_array($region, $zone->regions_json, true);

                return ['zone' => $zone, 'specificity' => $countryMatch ? ($regionMatch ? 2 : 1) : 0];
            })
            ->filter(fn (array $match): bool => $match['specificity'] > 0)
            ->sortBy([['specificity', 'desc'], ['zone.id', 'asc']])
            ->first()['zone'] ?? null;
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json;

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($config['amount'] ?? 0),
            ShippingRateType::Weight => $this->matchingRangeAmount(
                $config['ranges'] ?? [],
                $cart->lines->sum(fn ($line): int => $line->variant->requires_shipping
                    ? (int) ($line->variant->weight_g ?? 0) * $line->quantity
                    : 0),
                'min_g',
                'max_g',
            ),
            ShippingRateType::Price => $this->matchingRangeAmount(
                $config['ranges'] ?? [],
                $cart->lines->sum('line_subtotal_amount'),
                'min_amount',
                'max_amount',
            ),
            ShippingRateType::Carrier => (int) ($config['amount'] ?? 0),
        };
    }

    /** @param array<int, array<string, int>> $ranges */
    private function matchingRangeAmount(array $ranges, int $value, string $minimumKey, string $maximumKey): ?int
    {
        foreach ($ranges as $range) {
            if ($value >= ($range[$minimumKey] ?? 0)
                && (! isset($range[$maximumKey]) || $value <= $range[$maximumKey])) {
                return (int) $range['amount'];
            }
        }

        return null;
    }
}
