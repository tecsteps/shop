<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * General settings tab (spec 03 section 11.1): store details plus currency,
 * locale, timezone defaults, contact email, and order number prefix.
 */
class General extends Component
{
    use AuthorizesRequests, SendsToasts;

    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public string $contactEmail = '';

    public string $orderNumberPrefix = '#';

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('viewSettings', $store);

        $settings = $store->settings?->settings_json ?? [];

        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency ?? 'EUR';
        $this->defaultLocale = $store->default_locale ?? 'en';
        $this->timezone = $store->timezone ?? 'UTC';
        $this->contactEmail = (string) ($settings['contact_email'] ?? '');
        $this->orderNumberPrefix = (string) ($settings['order_number_prefix'] ?? '#');
    }

    public function save(): void
    {
        $store = $this->store();

        $this->authorize('updateSettings', $store);

        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'timezone'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'orderNumberPrefix' => ['nullable', 'string', 'max:10'],
        ]);

        $store->update([
            'name' => $this->storeName,
            'default_currency' => strtoupper($this->defaultCurrency),
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        $this->mergeSettings($store, [
            'store_name' => $this->storeName,
            'contact_email' => $this->contactEmail !== '' ? $this->contactEmail : null,
            'order_number_prefix' => $this->orderNumberPrefix !== '' ? $this->orderNumberPrefix : '#',
        ]);

        $this->toast(__('Settings saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.general', [
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    protected function store(): Store
    {
        return app('current_store');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function mergeSettings(Store $store, array $values): void
    {
        $settings = StoreSettings::query()->firstOrNew(['store_id' => $store->getKey()]);

        $settings->settings_json = array_filter(
            array_merge($settings->settings_json ?? [], $values),
            fn (mixed $value): bool => $value !== null,
        );

        $settings->save();
    }
}
