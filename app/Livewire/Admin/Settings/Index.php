<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreUserRole;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'general';

    // General
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'USD';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    // Domain modal
    public string $newHostname = '';

    public string $newDomainType = 'storefront';

    // Shipping zone modal
    public string $zoneName = '';

    public string $zoneCountries = '';

    public ?int $editZoneId = null;

    // Shipping rate modal
    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat_rate';

    public int $ratePrice = 0;

    // Tax settings
    public string $taxMode = 'manual';

    public int $taxRate = 0;

    public string $taxName = 'Tax';

    public bool $pricesIncludeTax = false;

    public function mount(): void
    {
        $user = auth()->user();
        $store = app('current_store');

        $role = $user->roleForStore($store);
        if ($role && ! in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin])) {
            abort(403);
        }

        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;

        $tax = TaxSettings::where('store_id', $store->id)->first();
        if ($tax) {
            $this->taxMode = $tax->mode instanceof \App\Enums\TaxMode ? $tax->mode->value : ($tax->mode ?? 'manual');
            $this->taxRate = $tax->rate ?? 0;
            $this->taxName = $tax->tax_name ?? 'Tax';
            $this->pricesIncludeTax = (bool) $tax->prices_include_tax;
        }
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'max:3'],
            'defaultLocale' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'string', 'max:64'],
        ]);

        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Settings saved.');
    }

    public function addDomain(): void
    {
        $this->validate([
            'newHostname' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');
        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => $this->newHostname,
            'type' => $this->newDomainType,
            'is_primary' => false,
            'tls_mode' => 'auto',
            'created_at' => now(),
        ]);

        $this->newHostname = '';
        $this->dispatch('toast', type: 'success', message: 'Domain added.');
        $this->modal('add-domain')->close();
    }

    public function setPrimary(int $domainId): void
    {
        $store = app('current_store');
        StoreDomain::where('store_id', $store->id)->update(['is_primary' => false]);
        StoreDomain::where('id', $domainId)->update(['is_primary' => true]);
        $this->dispatch('toast', type: 'success', message: 'Primary domain updated.');
    }

    public function deleteDomain(int $domainId): void
    {
        StoreDomain::where('id', $domainId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Domain removed.');
    }

    public function saveZone(): void
    {
        $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');
        $countries = array_map('trim', explode(',', $this->zoneCountries));

        if ($this->editZoneId) {
            ShippingZone::where('id', $this->editZoneId)->update([
                'name' => $this->zoneName,
                'countries_json' => $countries,
            ]);
        } else {
            ShippingZone::create([
                'store_id' => $store->id,
                'name' => $this->zoneName,
                'countries_json' => $countries,
                'regions_json' => [],
                'is_active' => true,
            ]);
        }

        $this->zoneName = '';
        $this->zoneCountries = '';
        $this->editZoneId = null;
        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved.');
        $this->modal('zone-form')->close();
    }

    public function editZone(int $zoneId): void
    {
        $zone = ShippingZone::find($zoneId);
        if ($zone) {
            $this->editZoneId = $zoneId;
            $this->zoneName = $zone->name;
            $this->zoneCountries = implode(', ', $zone->countries_json ?? []);
            $this->modal('zone-form')->show();
        }
    }

    public function deleteZone(int $zoneId): void
    {
        ShippingZone::where('id', $zoneId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Zone removed.');
    }

    public function openAddRate(int $zoneId): void
    {
        $this->rateZoneId = $zoneId;
        $this->rateName = '';
        $this->rateType = 'flat_rate';
        $this->ratePrice = 0;
        $this->modal('rate-form')->show();
    }

    public function saveRate(): void
    {
        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
        ]);

        ShippingRate::create([
            'zone_id' => $this->rateZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => ['price' => $this->ratePrice],
            'is_active' => true,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Rate added.');
        $this->modal('rate-form')->close();
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::where('id', $rateId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Rate removed.');
    }

    public function saveTax(): void
    {
        $store = app('current_store');

        TaxSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->taxMode,
                'rate' => $this->taxRate,
                'tax_name' => $this->taxName,
                'prices_include_tax' => $this->pricesIncludeTax,
                'is_active' => true,
            ],
        );

        $this->dispatch('toast', type: 'success', message: 'Tax settings saved.');
    }

    #[Computed]
    public function domains(): mixed
    {
        return StoreDomain::where('store_id', app('current_store')->id)->get();
    }

    #[Computed]
    public function shippingZones(): mixed
    {
        return ShippingZone::where('store_id', app('current_store')->id)
            ->with('rates')
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Settings']]]);
    }
}
