<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * @param  array<int, array{amount: int, label?: string}>  $lineAmounts  discounted line amounts
     * @param  array<string, mixed>  $address
     * @return array{lines: array<int, TaxLine>, total: int}
     */
    public function calculate(array $lineAmounts, TaxSettings $settings, array $address, int $shippingAmount = 0): array
    {
        if ($settings->mode === TaxMode::Provider || $this->resolveRate($settings, $address) === 0) {
            return ['lines' => [], 'total' => 0];
        }

        $rate = $this->resolveRate($settings, $address);

        $lines = [];
        $total = 0;

        foreach ($lineAmounts as $entry) {
            $amount = (int) $entry['amount'];
            $taxAmount = $settings->prices_include_tax
                ? $this->extractInclusive($amount, $rate)
                : $this->addExclusive($amount, $rate);

            $lines[] = new TaxLine($entry['label'] ?? 'Tax', $rate, $taxAmount);
            $total += $taxAmount;
        }

        if ($shippingAmount > 0 && ($settings->config_json['shipping_taxable'] ?? false)) {
            $shippingTax = $settings->prices_include_tax
                ? $this->extractInclusive($shippingAmount, $rate)
                : $this->addExclusive($shippingAmount, $rate);
            $lines[] = new TaxLine('Shipping tax', $rate, $shippingTax);
            $total += $shippingTax;
        }

        return ['lines' => $lines, 'total' => $total];
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($rateBasisPoints === 0) {
            return 0;
        }

        return (int) round($netAmount * $rateBasisPoints / 10000);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    protected function resolveRate(TaxSettings $settings, array $address): int
    {
        $config = $settings->config_json ?? [];
        $countryCode = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));

        $countryRates = $config['country_rates'] ?? [];

        if ($countryCode !== '' && isset($countryRates[$countryCode])) {
            return (int) $countryRates[$countryCode];
        }

        return (int) ($config['default_rate_bps'] ?? 0);
    }
}
