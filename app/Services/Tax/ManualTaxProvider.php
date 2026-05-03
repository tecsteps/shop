<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;
use App\ValueObjects\TaxResult;

class ManualTaxProvider implements TaxProvider
{
    /**
     * @param  array<int, int>  $amounts
     * @param  array<string, mixed>  $address
     */
    public function calculate(array $amounts, int $shippingAmount, TaxSettings $settings, array $address): TaxResult
    {
        $rate = $this->rateFor($settings, $address);

        if ($rate <= 0) {
            return new TaxResult([], 0, 0);
        }

        if ((bool) data_get($settings->config_json, 'shipping_taxable', true) && $shippingAmount > 0) {
            $amounts[] = $shippingAmount;
        }

        $taxAmount = collect($amounts)->sum(fn (int $amount): int => $settings->prices_include_tax
            ? $this->extractInclusive($amount, $rate)
            : $this->addExclusive($amount, $rate)
        );

        return new TaxResult([
            new TaxLine($this->nameFor($settings, $address), $rate, $taxAmount),
        ], $taxAmount, $rate);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function rateFor(TaxSettings $settings, array $address): int
    {
        $country = strtoupper((string) (data_get($address, 'country_code') ?: data_get($address, 'country')));
        $province = strtoupper((string) data_get($address, 'province_code'));

        foreach (data_get($settings->config_json, 'rates', []) as $rate) {
            $rateCountry = strtoupper((string) data_get($rate, 'country'));
            $rateProvince = strtoupper((string) data_get($rate, 'province_code'));

            if ($rateCountry !== '' && $rateCountry !== $country) {
                continue;
            }

            if ($rateProvince !== '' && $rateProvince !== $province) {
                continue;
            }

            return (int) data_get($rate, 'rate_bps', 0);
        }

        return (int) data_get($settings->config_json, 'default_rate_bps', 0);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function nameFor(TaxSettings $settings, array $address): string
    {
        $country = strtoupper((string) (data_get($address, 'country_code') ?: data_get($address, 'country')));

        foreach (data_get($settings->config_json, 'rates', []) as $rate) {
            if (strtoupper((string) data_get($rate, 'country')) === $country && data_get($rate, 'name')) {
                return (string) data_get($rate, 'name');
            }
        }

        return (string) data_get($settings->config_json, 'name', 'Tax');
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($netAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }
}
