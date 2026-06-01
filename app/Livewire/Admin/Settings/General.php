<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\StoreSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The Settings index (General tab). Hosts the tabbed settings shell; General,
 * Domains, Checkout, and Notifications render inline while Shipping and Taxes
 * link out to their dedicated routes. Restricted to owners and admins.
 */
#[Layout('livewire.admin.layout.app')]
class General extends Component
{
    use BindsCurrentStore;

    public string $tab = 'general';

    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'USD';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public function mount(): void
    {
        $this->authorizeSettings();

        $store = app('current_store');
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    private function authorizeSettings(): void
    {
        if (! \Illuminate\Support\Facades\Gate::allows('manage-store-settings')) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:64', 'timezone'],
        ];
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $this->validate();

        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => strtoupper($this->defaultCurrency),
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        // Mirror display settings into the settings bag for convenience.
        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            ['settings_json' => array_merge(
                $store->settings?->settings_json ?? [],
                ['store_name' => $this->storeName],
            )],
        );

        $this->dispatch('toast', type: 'success', message: __('Settings saved'));
    }

    /**
     * @return array<int, string>
     */
    public function getTimezonesProperty(): array
    {
        return timezone_identifiers_list();
    }

    public function render()
    {
        return view('livewire.admin.settings.general');
    }
}
