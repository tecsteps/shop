<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaxMode;
use App\Models\ShippingZone;
use App\Models\StoreSettings;
use App\Models\TaxSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends AdminController
{
    public function general(): View
    {
        $store = $this->currentStore();
        $settings = $this->storeSettings();

        return view('admin.settings.general', [
            'store' => $store,
            'settings' => $settings,
            'settingsJson' => $settings->settings_json ?? [],
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_locale' => ['required', 'string', 'max:16'],
            'timezone' => ['required', 'timezone'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'order_number_prefix' => ['nullable', 'string', 'max:16'],
            'order_number_start' => ['nullable', 'integer', 'min:1'],
        ]);

        $store = $this->currentStore();

        $store->update([
            'name' => $validated['name'],
            'default_currency' => strtoupper($validated['default_currency']),
            'default_locale' => $validated['default_locale'],
            'timezone' => $validated['timezone'],
        ]);

        $settings = $this->storeSettings();
        $json = $settings->settings_json ?? [];

        $json['store_name'] = $validated['name'];
        $json['contact_email'] = $validated['contact_email'];
        $json['order_number_prefix'] = $validated['order_number_prefix'] ?: '#';

        if ($validated['order_number_start'] !== null) {
            $json['order_number_start'] = $validated['order_number_start'];
        }

        $settings->update([
            'settings_json' => $json,
        ]);

        return back()->with('status', 'General settings saved.');
    }

    public function shipping(): View
    {
        $settings = $this->storeSettings();

        $zones = ShippingZone::query()
            ->with('rates')
            ->orderBy('name')
            ->get();

        return view('admin.settings.shipping', [
            'settingsJson' => $settings->settings_json ?? [],
            'zones' => $zones,
        ]);
    }

    public function updateShipping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'free_shipping_threshold_amount' => ['nullable', 'integer', 'min:0'],
            'default_shipping_rate_amount' => ['nullable', 'integer', 'min:0'],
            'bank_transfer_cancel_days' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $settings = $this->storeSettings();
        $json = $settings->settings_json ?? [];

        $shipping = (array) ($json['shipping'] ?? []);
        $shipping['free_shipping_threshold_amount'] = $validated['free_shipping_threshold_amount'];
        $shipping['default_shipping_rate_amount'] = $validated['default_shipping_rate_amount'];

        $json['shipping'] = $shipping;
        $json['bank_transfer_cancel_days'] = $validated['bank_transfer_cancel_days'];

        $settings->update([
            'settings_json' => $json,
        ]);

        return back()->with('status', 'Shipping settings saved.');
    }

    public function taxes(): View
    {
        $store = $this->currentStore();

        $taxSetting = TaxSetting::query()->firstOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => TaxMode::Manual->value,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => ['default_rate_bps' => 0],
            ]
        );

        return view('admin.settings.taxes', [
            'taxSetting' => $taxSetting,
            'configJson' => $taxSetting->config_json ?? [],
        ]);
    }

    public function updateTaxes(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in($this->enumValues(TaxMode::class))],
            'provider' => ['nullable', 'string', 'max:255'],
            'prices_include_tax' => ['nullable', 'boolean'],
            'default_rate_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'provider_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $store = $this->currentStore();

        $taxSetting = TaxSetting::query()->firstOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => TaxMode::Manual->value,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [],
            ]
        );

        $config = $taxSetting->config_json ?? [];
        $config['default_rate_bps'] = $validated['default_rate_bps'] ?? 0;

        if (! empty($validated['provider_api_key'])) {
            $config['provider_api_key'] = $validated['provider_api_key'];
        }

        $taxSetting->update([
            'mode' => $validated['mode'],
            'provider' => $validated['provider'] ?: 'none',
            'prices_include_tax' => $request->boolean('prices_include_tax'),
            'config_json' => $config,
        ]);

        return back()->with('status', 'Tax settings saved.');
    }

    protected function storeSettings(): StoreSettings
    {
        $store = $this->currentStore();

        return StoreSettings::query()->firstOrCreate(
            ['store_id' => $store->id],
            ['settings_json' => []],
        );
    }
}
