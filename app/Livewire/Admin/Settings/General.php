<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

#[\Livewire\Attributes\Layout('layouts.admin')]
class General extends AdminComponent
{
    public string $name = '';

    public string $currency = 'USD';

    public string $locale = 'en';

    public string $timezone = 'UTC';

    public string $contactEmail = '';

    public function mount(): void
    {
        $store = $this->currentStore();
        Gate::authorize('viewSettings', $store);
        $settings = $store->settings?->settings_json ?? [];
        $this->name = $store->name;
        $this->currency = $store->default_currency;
        $this->locale = $store->default_locale;
        $this->timezone = $store->timezone;
        $this->contactEmail = $settings['contact_email'] ?? '';
    }

    public function save(): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
        $validated = $this->validate(['name' => ['required', 'string', 'max:255'], 'currency' => ['required', 'string', 'size:3'], 'locale' => ['required', 'string', 'max:10'], 'timezone' => ['required', Rule::in(timezone_identifiers_list())], 'contactEmail' => ['nullable', 'email']]);
        $store = $this->currentStore();
        $store->update(['name' => $validated['name'], 'default_currency' => mb_strtoupper($validated['currency']), 'default_locale' => $validated['locale'], 'timezone' => $validated['timezone']]);
        $settings = $store->settings()->firstOrCreate(['store_id' => $store->getKey()]);
        $settings->update(['settings_json' => [...($settings->settings_json ?? []), 'contact_email' => $validated['contactEmail']]]);
        $this->toast('Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.general');
    }
}
