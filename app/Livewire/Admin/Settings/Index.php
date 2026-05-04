<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public bool $announcementEnabled = false;

    public string $announcementText = '';

    public bool $guestCheckoutEnabled = true;

    public string $newHostname = '';

    public string $newType = 'storefront';

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $settings = $this->storeSettings($store)->settings_json ?? [];

        $this->storeId = $store->getKey();
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
        $this->announcementEnabled = (bool) data_get($settings, 'announcement.enabled', false);
        $this->announcementText = (string) data_get($settings, 'announcement.text', '');
        $this->guestCheckoutEnabled = (bool) data_get($settings, 'checkout.guest_checkout_enabled', true);
    }

    public function save(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', Rule::in($this->currencyOptions())],
            'defaultLocale' => ['required', Rule::in(array_keys($this->localeOptions()))],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'announcementEnabled' => ['boolean'],
            'announcementText' => ['nullable', 'string', 'max:255'],
            'guestCheckoutEnabled' => ['boolean'],
        ]);

        $store->forceFill([
            'name' => $validated['storeName'],
            'default_currency' => $validated['defaultCurrency'],
            'default_locale' => $validated['defaultLocale'],
            'timezone' => $validated['timezone'],
        ])->save();

        $settings = $this->storeSettings($store);
        $settings->forceFill([
            'settings_json' => array_replace_recursive($settings->settings_json ?? [], [
                'announcement' => [
                    'enabled' => $this->announcementEnabled,
                    'text' => $this->announcementText,
                ],
                'checkout' => [
                    'guest_checkout_enabled' => $this->guestCheckoutEnabled,
                ],
            ]),
            'updated_at' => now(),
        ])->save();

        session()->flash('status', 'Settings saved');
        $this->dispatch('toast', type: 'success', message: __('Settings saved'));
    }

    public function addDomain(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'newHostname' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9.-]+$/i',
                Rule::unique('store_domains', 'hostname'),
            ],
            'newType' => ['required', Rule::in(array_column(StoreDomainType::cases(), 'value'))],
        ], [], [
            'newHostname' => 'hostname',
            'newType' => 'domain type',
        ]);

        StoreDomain::query()->create([
            'store_id' => $store->getKey(),
            'hostname' => mb_strtolower(trim($validated['newHostname'])),
            'type' => StoreDomainType::from($validated['newType']),
            'is_primary' => $this->domains()->isEmpty(),
            'tls_mode' => 'managed',
        ]);

        $this->reset('newHostname');

        session()->flash('status', 'Domain added');
        $this->dispatch('toast', type: 'success', message: __('Domain added'));
    }

    public function setPrimary(int $domainId): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $domain = $this->domain($domainId);

        StoreDomain::query()
            ->where('store_id', $store->getKey())
            ->update(['is_primary' => false]);

        $domain->forceFill(['is_primary' => true])->save();

        session()->flash('status', 'Primary domain updated');
    }

    public function removeDomain(int $domainId): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $domain = $this->domain($domainId);
        $wasPrimary = $domain->is_primary;

        $domain->delete();

        if ($wasPrimary) {
            StoreDomain::query()
                ->where('store_id', $store->getKey())
                ->orderBy('id')
                ->first()
                ?->forceFill(['is_primary' => true])
                ->save();
        }

        session()->flash('status', 'Domain removed');
    }

    /**
     * @return Collection<int, StoreDomain>
     */
    public function domains(): Collection
    {
        return StoreDomain::query()
            ->where('store_id', $this->storeId)
            ->orderByDesc('is_primary')
            ->orderBy('hostname')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    public function localeOptions(): array
    {
        return [
            'en' => 'English',
            'de' => 'German',
            'fr' => 'French',
        ];
    }

    /**
     * @return list<string>
     */
    public function currencyOptions(): array
    {
        return ['EUR', 'USD', 'GBP', 'CHF'];
    }

    /**
     * @return list<string>
     */
    public function timezoneOptions(): array
    {
        return collect(timezone_identifiers_list())
            ->filter(fn (string $timezone): bool => str_starts_with($timezone, 'Europe/') || str_starts_with($timezone, 'America/'))
            ->values()
            ->all();
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.index', [
            'domains' => $this->domains(),
            'currencyOptions' => $this->currencyOptions(),
            'localeOptions' => $this->localeOptions(),
            'timezoneOptions' => $this->timezoneOptions(),
        ])->layout('layouts.app', [
            'title' => __('Settings'),
        ]);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->whereKey($this->storeId)->firstOrFail();
    }

    private function storeSettings(Store $store): StoreSettings
    {
        return StoreSettings::query()->firstOrCreate(
            ['store_id' => $store->getKey()],
            ['settings_json' => []],
        );
    }

    private function domain(int $domainId): StoreDomain
    {
        return StoreDomain::query()
            ->where('store_id', $this->storeId)
            ->whereKey($domainId)
            ->firstOrFail();
    }
}
