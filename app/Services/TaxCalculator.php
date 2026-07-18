<?php

namespace App\Services;

use App\Models\Store;
use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    public function __construct(private readonly ShippingCalculator $shippingCalculator) {}

    /** @param array<string, mixed> $address */
    public function calculate(int $amount, TaxSettings $settings, array $address): TaxLine
    {
        $rate = $this->resolveRate($settings, $address);
        $tax = $settings->prices_include_tax
            ? $this->extractInclusive($amount, $rate)
            : $this->addExclusive($amount, $rate);

        return new TaxLine((string) ($settings->config_json['label'] ?? 'Tax'), $rate, $tax);
    }

    /** @param array<int, int> $amounts
     * @param  array<string, mixed>  $address
     * @return array<int, TaxLine>
     */
    public function calculateLines(array $amounts, TaxSettings $settings, array $address): array
    {
        return array_map(fn (int $amount): TaxLine => $this->calculate($amount, $settings, $address), $amounts);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return intdiv(($netAmount * $rateBasisPoints) + 5000, 10000);
    }

    /** @param array<string, mixed> $address */
    private function resolveRate(TaxSettings $settings, array $address): int
    {
        $store = Store::query()->findOrFail($settings->store_id);
        $zone = $this->shippingCalculator->getMatchingZone($store, $address);
        $zoneRates = $settings->config_json['zone_rates'] ?? [];

        if ($zone && array_key_exists((string) $zone->id, $zoneRates)) {
            return (int) $zoneRates[(string) $zone->id];
        }

        return (int) ($settings->config_json['default_rate'] ?? 0);
    }
}
