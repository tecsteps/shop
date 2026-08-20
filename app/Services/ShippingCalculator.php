<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\Store;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    public function getAvailableRates(Store $store, array $address): Collection
    {
        $country = strtoupper((string) ($address['country_code'] ?? ''));
        $region = strtoupper((string) ($address['province_code'] ?? ''));

        return ShippingRate::query()->where('is_active', true)->whereHas('zone', function ($query) use ($store, $country, $region): void {
            $query->where('store_id', $store->getKey())->where(function ($zone) use ($country, $region): void {
                $zone->whereJsonContains('countries_json', $country)->orWhereNull('countries_json');

                if ($region !== '') {
                    $zone->orWhereJsonContains('regions_json', $region);
                }
            });
        })->with('zone')->get();
    }

    public function calculate(ShippingRate $rate, Cart $cart): int
    {
        $lines = $cart->load('lines.variant')->lines;
        $lines = $lines->filter(fn ($line): bool => (bool) $line->variant->requires_shipping);
        $weight = (int) $lines->sum(fn ($line): int => $line->quantity * ($line->variant->weight_g ?? $line->variant->weight_grams));
        $subtotal = (int) $lines->sum('line_total_amount');
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            'weight' => $this->rangeAmount($config['ranges'] ?? [], $weight, $rate->price_amount),
            'price' => $this->rangeAmount($config['ranges'] ?? [], $subtotal, $rate->price_amount),
            default => $rate->price_amount,
        };
    }

    private function rangeAmount(array $ranges, int $value, int $fallback): int
    {
        foreach ($ranges as $range) {
            if ($value >= (int) ($range['min_g'] ?? $range['min_amount'] ?? 0) && $value <= (int) ($range['max_g'] ?? $range['max_amount'] ?? PHP_INT_MAX)) {
                return (int) ($range['amount'] ?? $fallback);
            }
        }

        return $fallback;
    }
}
