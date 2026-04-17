<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\TaxSetting;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

final class TaxCalculator
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function calculate(int $amount, TaxSetting $settings, array $address = []): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $rateBasisPoints = $this->resolveRateBasisPoints($settings, $address);

        if ($rateBasisPoints <= 0) {
            return 0;
        }

        if ($settings->prices_include_tax) {
            return $this->extractInclusive($amount, $rateBasisPoints);
        }

        return $this->addExclusive($amount, $rateBasisPoints);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        $netAmount = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return max(0, $grossAmount - $netAmount);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        if ($netAmount <= 0 || $rateBasisPoints <= 0) {
            return 0;
        }

        return (int) round(($netAmount * $rateBasisPoints) / 10000);
    }

    /**
     * @param  list<int>  $lineAmounts
     * @param  array<string, mixed>  $address
     */
    public function calculateForAmounts(array $lineAmounts, int $shippingAmount, TaxSetting $settings, array $address = []): TaxCalculationResult
    {
        if ($settings->mode !== TaxMode::Manual) {
            return TaxCalculationResult::zero();
        }

        $rateBasisPoints = $this->resolveRateBasisPoints($settings, $address);

        if ($rateBasisPoints <= 0) {
            return TaxCalculationResult::zero();
        }

        $taxLines = [];
        $taxTotal = 0;

        foreach ($lineAmounts as $lineAmount) {
            if ($lineAmount <= 0) {
                continue;
            }

            $lineTax = $settings->prices_include_tax
                ? $this->extractInclusive($lineAmount, $rateBasisPoints)
                : $this->addExclusive($lineAmount, $rateBasisPoints);

            if ($lineTax <= 0) {
                continue;
            }

            $taxLines[] = new TaxLine(name: 'Line Tax', rate: $rateBasisPoints, amount: $lineTax);
            $taxTotal += $lineTax;
        }

        if ($shippingAmount > 0 && $this->isShippingTaxable($settings)) {
            $shippingTax = $settings->prices_include_tax
                ? $this->extractInclusive($shippingAmount, $rateBasisPoints)
                : $this->addExclusive($shippingAmount, $rateBasisPoints);

            if ($shippingTax > 0) {
                $taxLines[] = new TaxLine(name: 'Shipping Tax', rate: $rateBasisPoints, amount: $shippingTax);
                $taxTotal += $shippingTax;
            }
        }

        return new TaxCalculationResult(
            taxLines: $taxLines,
            totalAmount: $taxTotal,
            rateBasisPoints: $rateBasisPoints,
        );
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function resolveRateBasisPoints(TaxSetting $settings, array $address = []): int
    {
        if ($settings->mode !== TaxMode::Manual) {
            return 0;
        }

        $config = is_array($settings->config_json) ? $settings->config_json : [];

        $defaultRate = $this->asInt($config['default_rate_bps'] ?? 0);

        $regionCode = $this->normalizeCode($address['province_code'] ?? $address['region_code'] ?? $address['province'] ?? null);
        $countryCode = $this->normalizeCode($address['country_code'] ?? $address['country'] ?? null);

        $ratesByRegion = $config['rates_by_region'] ?? null;

        if (is_array($ratesByRegion) && $regionCode !== null) {
            $regionRate = $ratesByRegion[$regionCode] ?? null;

            if ($regionRate !== null) {
                return $this->asInt($regionRate);
            }
        }

        $ratesByCountry = $config['rates_by_country'] ?? null;

        if (is_array($ratesByCountry) && $countryCode !== null) {
            $countryRate = $ratesByCountry[$countryCode] ?? null;

            if ($countryRate !== null) {
                return $this->asInt($countryRate);
            }
        }

        $zoneRate = $this->resolveZoneRate($config, $countryCode, $regionCode);

        if ($zoneRate !== null) {
            return $zoneRate;
        }

        return max(0, $defaultRate);
    }

    private function isShippingTaxable(TaxSetting $settings): bool
    {
        $config = is_array($settings->config_json) ? $settings->config_json : [];
        $value = $config['shipping_taxable'] ?? true;

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveZoneRate(array $config, ?string $countryCode, ?string $regionCode): ?int
    {
        $zoneRates = $config['zone_rates'] ?? $config['zones'] ?? null;

        if (! is_array($zoneRates) || $countryCode === null) {
            return null;
        }

        $bestRate = null;
        $bestSpecificity = -1;

        foreach ($zoneRates as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $countries = $this->normalizeCodeList($entry['countries'] ?? []);
            $regions = $this->normalizeCodeList($entry['regions'] ?? []);

            if (! in_array($countryCode, $countries, true)) {
                continue;
            }

            $regionMatch = $regionCode !== null && in_array($regionCode, $regions, true);
            $specificity = $regionMatch ? 2 : 1;

            if ($specificity < $bestSpecificity) {
                continue;
            }

            $rate = $this->asInt($entry['rate_bps'] ?? 0);

            if ($rate < 0) {
                continue;
            }

            if ($specificity > $bestSpecificity || $bestRate === null) {
                $bestRate = $rate;
                $bestSpecificity = $specificity;
            }
        }

        return $bestRate;
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
    private function normalizeCodeList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $codes = [];

        foreach ($value as $item) {
            $code = $this->normalizeCode($item);

            if ($code === null) {
                continue;
            }

            $codes[] = $code;
        }

        return array_values(array_unique($codes));
    }
}
