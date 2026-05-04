<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\TaxMode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\TaxSettingsResource;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxSettingsController extends Controller
{
    public function show(Request $request, Store $store): TaxSettingsResource
    {
        $this->authorizeStore($request, $store);

        return TaxSettingsResource::make($this->settings($store));
    }

    public function update(Request $request, Store $store): TaxSettingsResource
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'mode' => ['required', Rule::in(array_map(fn (TaxMode $mode): string => $mode->value, TaxMode::cases()))],
            'provider' => ['required_if:mode,provider', Rule::in(['none', 'stripe_tax'])],
            'prices_include_tax' => ['required', 'boolean'],
            'config_json' => ['required', 'array'],
            'config_json.default_tax_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'config_json.default_rate_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'config_json.tax_rates' => ['nullable', 'array'],
            'config_json.tax_rates.*.country_code' => ['required_with:config_json.tax_rates', 'string', 'size:2'],
            'config_json.tax_rates.*.rate' => ['required_with:config_json.tax_rates', 'integer', 'min:0', 'max:10000'],
            'config_json.tax_rates.*.name' => ['required_with:config_json.tax_rates', 'string', 'max:50'],
            'config_json.tax_rates.*.shipping_taxed' => ['nullable', 'boolean'],
            'config_json.rates' => ['nullable', 'array'],
            'config_json.rates.*.country' => ['required_with:config_json.rates', 'string', 'size:2'],
            'config_json.rates.*.rate_bps' => ['required_with:config_json.rates', 'integer', 'min:0', 'max:10000'],
            'config_json.rates.*.name' => ['required_with:config_json.rates', 'string', 'max:50'],
        ]);

        $settings = TaxSettings::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->getKey()],
            [
                'mode' => TaxMode::from($validated['mode']),
                'provider' => $validated['mode'] === TaxMode::Provider->value ? $validated['provider'] : 'none',
                'prices_include_tax' => (bool) $validated['prices_include_tax'],
                'config_json' => $this->normalizeConfig($request->input('config_json', [])),
            ],
        );

        return TaxSettingsResource::make($settings->refresh());
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function settings(Store $store): TaxSettings
    {
        return TaxSettings::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->getKey()],
            [
                'mode' => TaxMode::Manual,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [
                    'name' => 'Tax',
                    'default_rate_bps' => 0,
                    'shipping_taxable' => true,
                    'rates' => [],
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        $rates = collect($config['tax_rates'] ?? $config['rates'] ?? [])
            ->map(fn (array $rate): array => [
                'country' => strtoupper((string) ($rate['country_code'] ?? $rate['country'])),
                'rate_bps' => (int) ($rate['rate'] ?? $rate['rate_bps']),
                'name' => (string) $rate['name'],
                'shipping_taxed' => (bool) ($rate['shipping_taxed'] ?? true),
            ])
            ->values()
            ->all();

        return [
            ...$config,
            'default_rate_bps' => (int) ($config['default_tax_rate'] ?? $config['default_rate_bps'] ?? 0),
            'shipping_taxable' => (bool) ($config['shipping_taxable'] ?? true),
            'rates' => $rates,
        ];
    }
}
