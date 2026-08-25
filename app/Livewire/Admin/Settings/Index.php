<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\StoreDomain;
use DateTimeZone;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public string $tab = 'general';

    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'USD';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public string $newHostname = '';

    public string $newType = 'storefront';

    public bool $showAddDomain = false;

    public function mount(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $store = app('current_store');

        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    #[Computed]
    public function timezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    #[Computed]
    public function domains(): Collection
    {
        return app('current_store')->domains()->orderByDesc('is_primary')->get();
    }

    public function save(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'max:8'],
            'defaultLocale' => ['required', 'string', 'max:16'],
            'timezone' => ['required', 'string', 'max:255'],
        ]);

        app('current_store')->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        $this->toast('Settings saved');
    }

    public function addDomain(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $this->validate([
            'newHostname' => ['required', 'string', 'max:255'],
            'newType' => ['required', 'in:storefront,admin,api'],
        ]);

        $store = app('current_store');

        $isPrimary = $store->domains()->count() === 0 || $this->newType === 'storefront';

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => $this->newHostname,
            'type' => $this->newType,
            'is_primary' => $isPrimary,
            'tls_mode' => 'none',
        ]);

        if ($isPrimary) {
            $store->domains()->where('type', 'storefront')->where('hostname', '!=', $this->newHostname)->update(['is_primary' => false]);
        }

        $this->newHostname = '';
        $this->showAddDomain = false;
        $this->toast('Domain added');
    }

    public function removeDomain(int $domainId): void
    {
        $this->authorize('updateSettings', app('current_store'));

        StoreDomain::where('id', $domainId)->where('store_id', app('current_store')->id)->delete();

        $this->toast('Domain removed');
    }

    public function setPrimary(int $domainId): void
    {
        $this->authorize('updateSettings', app('current_store'));

        app('current_store')->domains()->update(['is_primary' => false]);
        StoreDomain::where('id', $domainId)->where('store_id', app('current_store')->id)->update(['is_primary' => true]);

        $this->toast('Primary domain updated');
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }
}
