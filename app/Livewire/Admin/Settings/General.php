<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Livewire\Component;

class General extends Component
{
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    /** @var array<string, list<string>> */
    protected array $rules = [
        'storeName' => ['required', 'string', 'max:255'],
        'defaultCurrency' => ['required', 'string', 'in:EUR,USD,GBP,CHF,JPY,CAD,AUD'],
        'defaultLocale' => ['required', 'string', 'in:en,de,fr,es,it,nl,pt'],
        'timezone' => ['required', 'string'],
    ];

    public function mount(): void
    {
        /** @var Store $store */
        $store = app('current_store');
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency ?? 'EUR';

        $settings = $store->settings?->settings_json ?? [];
        $this->defaultLocale = $settings['default_locale'] ?? 'en';
        $this->timezone = $settings['timezone'] ?? 'UTC';
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
        ]);

        $store->settings()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'settings_json' => array_merge(
                    $store->settings?->settings_json ?? [],
                    [
                        'default_locale' => $this->defaultLocale,
                        'timezone' => $this->timezone,
                    ]
                ),
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings.general');
    }
}
