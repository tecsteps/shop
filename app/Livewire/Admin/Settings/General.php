<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreSettings;
use Livewire\Component;

class General extends Component
{
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public string $message = '';

    public function mount(): void
    {
        $this->authorize('update', app('current_store'));
        $store = app('current_store');
        $settings = $store->settings;
        $general = $settings?->general_json ?? [];
        $this->storeName = (string) ($general['store_name'] ?? $store->name);
        $this->storeHandle = (string) $store->handle;
        $this->defaultCurrency = (string) $store->default_currency;
        $this->defaultLocale = (string) $store->default_locale;
        $this->timezone = (string) $store->timezone;
    }

    public function save(): void
    {
        $this->authorize('update', app('current_store'));
        $data = $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'timezone'],
        ]);
        $store = app('current_store');
        $settings = StoreSettings::query()->first();
        $store->update([
            'name' => $data['storeName'],
            'default_currency' => strtoupper($data['defaultCurrency']),
            'default_locale' => $data['defaultLocale'],
            'timezone' => $data['timezone'],
        ]);
        StoreSettings::updateOrCreate(
            ['store_id' => $store->getKey()],
            ['general_json' => array_merge($settings?->general_json ?? [], ['store_name' => $data['storeName']])],
        );
        $this->message = 'General settings saved.';
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.general', [
            'currencies' => ['EUR' => 'Euro (EUR)', 'USD' => 'US Dollar (USD)', 'GBP' => 'British Pound (GBP)', 'CHF' => 'Swiss Franc (CHF)', 'CAD' => 'Canadian Dollar (CAD)', 'AUD' => 'Australian Dollar (AUD)'],
            'locales' => ['en' => 'English', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish'],
            'timezones' => \DateTimeZone::listIdentifiers(),
        ])->layout('layouts.admin');
    }
}
