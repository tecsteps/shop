<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Store;
use App\Models\TaxSetting;

class TaxService
{
    /**
     * @param  array<string, mixed>|null  $address
     * @return array{
     *     rate_bps: int,
     *     prices_include_tax: bool,
     *     line_taxes: array<int, int>,
     *     shipping_tax: int,
     *     tax_lines: array<int, array<string, mixed>>,
     *     tax_total: int,
     *     provider_snapshot: array<string, mixed>
     * }
     */
    public function calculate(Store $store, Cart $cart, int $shippingAmount, ?array $address = null): array
    {
        $cart->loadMissing('lines.variant');

        $setting = TaxSetting::query()->where('store_id', $store->id)->first();

        $config = (array) ($setting?->config_json ?? []);
        $rate = $this->resolveRate($config, $address);
        $pricesIncludeTax = (bool) ($setting?->prices_include_tax ?? false);

        $lineTaxes = [];
        $taxTotal = 0;

        foreach ($cart->lines as $line) {
            $lineAmount = (int) $line->line_total_amount;

            $lineTax = $pricesIncludeTax
                ? $this->extractIncludedTax($lineAmount, $rate)
                : $this->calculateExclusiveTax($lineAmount, $rate);

            $lineTaxes[$line->id] = $lineTax;
            $taxTotal += $lineTax;
        }

        $shippingTax = $pricesIncludeTax
            ? $this->extractIncludedTax($shippingAmount, $rate)
            : $this->calculateExclusiveTax($shippingAmount, $rate);

        $taxTotal += $shippingTax;

        return [
            'rate_bps' => $rate,
            'prices_include_tax' => $pricesIncludeTax,
            'line_taxes' => $lineTaxes,
            'shipping_tax' => $shippingTax,
            'tax_lines' => [
                [
                    'name' => 'Tax',
                    'rate' => $rate,
                    'amount' => $taxTotal,
                ],
            ],
            'tax_total' => $taxTotal,
            'provider_snapshot' => [
                'provider' => 'manual',
                'calculated_at' => now()->toIso8601String(),
                'rate_bps' => $rate,
                'line_taxes' => $lineTaxes,
                'shipping_tax_amount' => $shippingTax,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>|null  $address
     */
    private function resolveRate(array $config, ?array $address): int
    {
        $country = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $province = strtoupper((string) ($address['province_code'] ?? ''));

        $ratesByCountry = (array) ($config['rates_by_country'] ?? []);

        if ($country !== '' && array_key_exists($country, $ratesByCountry)) {
            $countryRate = $ratesByCountry[$country];

            if (is_array($countryRate)) {
                $byProvince = (array) ($countryRate['by_province'] ?? []);

                if ($province !== '' && array_key_exists($province, $byProvince)) {
                    return (int) $byProvince[$province];
                }

                if (array_key_exists('default', $countryRate)) {
                    return (int) $countryRate['default'];
                }
            }

            if (is_numeric($countryRate)) {
                return (int) $countryRate;
            }
        }

        return (int) ($config['default_rate_bps'] ?? 0);
    }

    private function calculateExclusiveTax(int $amount, int $rateBps): int
    {
        if ($amount <= 0 || $rateBps <= 0) {
            return 0;
        }

        return (int) round(($amount * $rateBps) / 10000);
    }

    private function extractIncludedTax(int $grossAmount, int $rateBps): int
    {
        if ($grossAmount <= 0 || $rateBps <= 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBps);

        return $grossAmount - $net;
    }
}
