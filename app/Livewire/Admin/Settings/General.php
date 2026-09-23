<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreSettings;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class General extends Component
{
    private const LOCALE_OPTIONS = [
        'en' => 'English',
        'de' => 'German',
        'fr' => 'French',
        'es' => 'Spanish',
        'it' => 'Italian',
        'nl' => 'Dutch',
        'pt' => 'Portuguese',
        'sv' => 'Swedish',
        'da' => 'Danish',
        'fi' => 'Finnish',
        'pl' => 'Polish',
        'cs' => 'Czech',
        'ja' => 'Japanese',
        'zh' => 'Chinese',
    ];

    public string $storeName = '';

    public string $storeHandle = '';

    public string $contactEmail = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'Europe/Berlin';

    public function mount(): void
    {
        Gate::authorize('manage-store-settings');
        $store = app('current_store');
        $settings = $store->settings?->settings_json ?? [];
        $this->storeName = $settings['store_name'] ?? $store->name;
        $this->storeHandle = $store->handle;
        $this->contactEmail = $settings['contact_email'] ?? '';
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        Gate::authorize('manage-store-settings');
        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['required', 'email', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:12', function (string $attribute, mixed $value, \Closure $fail): void {
                $normalizedLocale = \Locale::canonicalize(str_replace('-', '_', $value));

                if (! in_array($normalizedLocale, \ResourceBundle::getLocales(''), true)) {
                    $fail('The selected locale must be a valid locale code.');
                }
            }],
            'timezone' => ['required', 'timezone'],
        ]);
        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => strtoupper($this->defaultCurrency),
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);
        $settings = $store->settings?->settings_json ?? [];
        StoreSettings::updateOrCreate(['store_id' => $store->id], ['settings_json' => [...$settings, 'store_name' => $this->storeName, 'contact_email' => $this->contactEmail]]);
        session()->flash('status', 'Store settings saved.');
    }

    public function render(): mixed
    {
        $locales = self::LOCALE_OPTIONS;

        if (! array_key_exists($this->defaultLocale, $locales)) {
            $locales[$this->defaultLocale] = "Current locale ({$this->defaultLocale})";
        }

        return view('livewire.admin.settings.general', [
            'locales' => $locales,
            'timezones' => \DateTimeZone::listIdentifiers(),
        ])->layout('layouts.admin');
    }
}
