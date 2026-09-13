<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSetting;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Tax calculation orchestration per spec 05 section 8.
 */
class TaxCalculator
{
    public function __construct(
        protected ManualTaxProvider $manual,
        protected StripeTaxProvider $stripe,
    ) {}

    /**
     * @param  array<int, array{amount: int, variant_id?: ?int}>  $lineItems  Discounted line amounts in cents.
     * @param  array<string, mixed>  $address
     */
    public function calculate(Store $store, array $lineItems, int $shippingAmount, array $address): TaxCalculationResult
    {
        $settings = TaxSetting::query()->where('store_id', $store->getKey())->first();

        if ($settings === null) {
            return new TaxCalculationResult([], 0);
        }

        $resolution = $this->resolveRate($settings, $address);

        $request = new TaxCalculationRequest(
            lineItems: $lineItems,
            shippingAmount: $shippingAmount,
            address: $address,
            pricesIncludeTax: (bool) $settings->prices_include_tax,
            rate: $resolution['rate'],
            rateName: $resolution['name'],
            shippingTaxed: $resolution['shipping_taxed'],
            config: $settings->config_json ?? [],
        );

        if ($settings->mode === TaxMode::Provider) {
            return $this->stripe->calculate($request);
        }

        return $this->manual->calculate($request);
    }

    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        return $this->manual->extractInclusive($grossAmount, $rateBasisPoints);
    }

    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return $this->manual->addExclusive($netAmount, $rateBasisPoints);
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{rate: int, name: string, shipping_taxed: bool}
     */
    public function resolveRate(TaxSetting $settings, array $address): array
    {
        $config = $settings->config_json ?? [];
        $countryCode = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));

        $default = [
            'rate' => (int) ($config['default_tax_rate'] ?? $config['default_rate'] ?? $config['rate'] ?? 0),
            'name' => (string) ($config['default_tax_name'] ?? 'Tax'),
            'shipping_taxed' => (bool) ($config['shipping_taxed'] ?? true),
        ];

        foreach ((array) ($config['tax_rates'] ?? []) as $entry) {
            if (strtoupper((string) ($entry['country_code'] ?? '')) === $countryCode && $countryCode !== '') {
                return [
                    'rate' => (int) ($entry['rate'] ?? 0),
                    'name' => (string) ($entry['name'] ?? $default['name']),
                    'shipping_taxed' => (bool) ($entry['shipping_taxed'] ?? true),
                ];
            }
        }

        return $default;
    }
}
