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
        $country = $address['country_code'] ?? $address['country'] ?? null;
        $region = $address['province_code'] ?? $address['region'] ?? null;

        $zone = ShippingZone::query()
            ->where('store_id', $store->id)
            ->with('rates')
            ->get()
            ->map(function (ShippingZone $zone) use ($country, $region): array {
                $countryMatches = collect($zone->countries_json)->contains($country);
                $regionMatches = $region !== null && collect($zone->regions_json)->contains($region);

                return ['zone' => $zone, 'specificity' => $countryMatches ? ($regionMatches ? 2 : 1) : -1];
            })
            ->filter(fn (array $match): bool => $match['specificity'] >= 0)
            ->sortBy([['specificity', 'desc'], [fn (array $match): int => $match['zone']->id, 'asc']])
            ->first()['zone'] ?? null;

        return $zone?->rates->where('is_active', true)->values() ?? collect();
    }

    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        $config = $rate->config_json;

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($config['amount'] ?? 0),
            ShippingRateType::Weight => $this->calculateRange((int) $cart->lines->sum(fn ($line): int => $line->variant->requires_shipping ? $line->variant->weight_g * $line->quantity : 0), $config['ranges'] ?? [], 'min_g', 'max_g'),
            ShippingRateType::Price => $this->calculateRange((int) $cart->lines->sum('line_total_amount'), $config['ranges'] ?? [], 'min_amount', 'max_amount'),
            ShippingRateType::Carrier => (int) ($config['fallback_amount'] ?? 0),
        };
    }

    /** @param list<array<string, int>> $ranges */
    private function calculateRange(int $value, array $ranges, string $minimumKey, string $maximumKey): ?int
    {
        foreach ($ranges as $range) {
            if ($value >= ($range[$minimumKey] ?? 0) && (! isset($range[$maximumKey]) || $value <= $range[$maximumKey])) {
                return (int) $range['amount'];
            }
        }

        return null;
    }
}
