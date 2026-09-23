<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreSettings;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class General extends Component
{
    public string $storeName = '';

    public string $contactEmail = '';

    public string $defaultCurrency = 'EUR';

    public string $timezone = 'Europe/Berlin';

    public function mount(): void
    {
        Gate::authorize('manage-store-settings');
        $store = app('current_store');
        $settings = $store->settings?->settings_json ?? [];
        $this->storeName = $settings['store_name'] ?? $store->name;
        $this->contactEmail = $settings['contact_email'] ?? '';
        $this->defaultCurrency = $store->default_currency;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        Gate::authorize('manage-store-settings');
        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['required', 'email', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
        ]);
        $store = app('current_store');
        $store->update(['name' => $this->storeName, 'default_currency' => strtoupper($this->defaultCurrency), 'timezone' => $this->timezone]);
        $settings = $store->settings?->settings_json ?? [];
        StoreSettings::updateOrCreate(['store_id' => $store->id], ['settings_json' => [...$settings, 'store_name' => $this->storeName, 'contact_email' => $this->contactEmail]]);
        session()->flash('status', 'Store settings saved.');
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.general')->layout('layouts.admin');
    }
}
