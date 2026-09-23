<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreDomainType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Domains extends Component
{
    public Collection $domains;

    public string $newHostname = '';

    public string $newType = 'storefront';

    public bool $showAddDomainModal = false;

    public function mount(): void
    {
        $this->authorizeStoreSettings();
        $this->loadDomains();
    }

    public function openAddDomainModal(): void
    {
        $this->authorizeStoreSettings();
        $this->resetValidation();
        $this->newHostname = '';
        $this->newType = StoreDomainType::Storefront->value;
        $this->showAddDomainModal = true;
    }

    public function closeAddDomainModal(): void
    {
        $this->authorizeStoreSettings();
        $this->showAddDomainModal = false;
        $this->resetValidation();
    }

    public function addDomain(): void
    {
        $store = $this->authorizeStoreSettings();
        $hostname = strtolower(trim($this->newHostname));
        $this->newHostname = $hostname;

        $validated = $this->validate([
            'newHostname' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/', 'unique:store_domains,hostname'],
            'newType' => ['required', 'string', Rule::enum(StoreDomainType::class)],
        ]);
        $isFirstDomainForType = ! $store->domains()->where('type', $validated['newType'])->where('is_primary', true)->exists();

        $domain = $store->domains()->create([
            'hostname' => $validated['newHostname'],
            'type' => $validated['newType'],
            'is_primary' => $isFirstDomainForType,
            'tls_mode' => 'managed',
            'created_at' => now(),
        ]);

        $this->showAddDomainModal = false;
        $this->loadDomains();
        Cache::forget("store-domain:{$domain->hostname}");
        $this->dispatch('toast', type: 'success', message: 'Domain added.');
    }

    public function removeDomain(int $domainId): void
    {
        $store = $this->authorizeStoreSettings();
        $domain = $store->domains()->findOrFail($domainId);
        $hostname = $domain->hostname;
        $wasPrimary = $domain->is_primary;
        $type = $domain->type;

        DB::transaction(function () use ($domain, $store, $type, $wasPrimary): void {
            $domain->delete();

            if ($wasPrimary) {
                $replacement = $store->domains()->where('type', $type)->orderBy('id')->first();
                $replacement?->update(['is_primary' => true]);
            }
        });

        Cache::forget("store-domain:{$hostname}");
        $this->loadDomains();
        $this->dispatch('toast', type: 'success', message: 'Domain removed.');
    }

    public function setPrimary(int $domainId): void
    {
        $store = $this->authorizeStoreSettings();
        $domain = $store->domains()->findOrFail($domainId);

        DB::transaction(function () use ($domain, $store): void {
            $store->domains()->where('type', $domain->type)->update(['is_primary' => false]);
            $domain->update(['is_primary' => true]);
        });

        Cache::forget("store-domain:{$domain->hostname}");
        $this->loadDomains();
        $this->dispatch('toast', type: 'success', message: 'Primary domain updated.');
    }

    public function render(): mixed
    {
        $this->authorizeStoreSettings();

        return view('livewire.admin.settings.domains', ['domains' => $this->domains]);
    }

    private function authorizeStoreSettings(): Store
    {
        $store = $this->currentStore();
        Gate::authorize('manage-store-settings');

        return $store;
    }

    private function loadDomains(): void
    {
        $this->domains = $this->currentStore()->domains()->orderBy('type')->orderBy('hostname')->get();
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        $user = auth()->user();

        if (! $store instanceof Store || ! $user?->stores()->whereKey($store->id)->exists()) {
            $storeId = session()->get('current_store_id');
            $store = $user === null || $storeId === null ? null : $user->stores()->whereKey($storeId)->first();
        }

        abort_unless($store instanceof Store, 404);
        app()->instance('current_store', $store);
        view()->share('currentStore', $store);

        return $store;
    }
}
