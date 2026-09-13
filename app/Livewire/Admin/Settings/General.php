<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class General extends Component
{
    use ResolvesAdminStore;

    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'Europe/Berlin';

    public string $contactEmail = '';

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->store());

        $store = $this->store();
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
        $this->contactEmail = (string) ($store->storeSetting?->settings_json['contact_email'] ?? '');
    }

    public function save(): void
    {
        Gate::authorize('updateSettings', $this->store());

        $validated = $this->validate([
            'storeName' => 'required|string|max:255',
            'defaultCurrency' => 'required|string|size:3',
            'defaultLocale' => 'required|string|max:10',
            'timezone' => 'required|string|timezone',
            'contactEmail' => 'nullable|email|max:255',
        ]);

        $store = $this->store();
        $store->update([
            'name' => $validated['storeName'],
            'default_currency' => strtoupper($validated['defaultCurrency']),
            'default_locale' => $validated['defaultLocale'],
            'timezone' => $validated['timezone'],
        ]);

        $settings = $store->storeSetting?->settings_json ?? [];
        $settings['store_name'] = $validated['storeName'];
        $settings['contact_email'] = $validated['contactEmail'] ?? '';

        $store->storeSetting()->updateOrCreate(
            ['store_id' => $store->getKey()],
            ['settings_json' => $settings]
        );

        $this->toast('success', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.general', [
            'timezones' => timezone_identifiers_list(),
        ]);
    }
}
