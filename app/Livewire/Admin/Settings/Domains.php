<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreDomainType;
use App\Enums\TlsMode;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Domains extends Component
{
    use ResolvesAdminStore;

    public string $newHostname = '';

    public string $newType = 'storefront';

    public bool $showAddModal = false;

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->store());
    }

    public function addDomain(): void
    {
        Gate::authorize('updateSettings', $this->store());

        $validated = $this->validate([
            'newHostname' => 'required|string|max:255|unique:store_domains,hostname',
            'newType' => 'required|in:storefront,admin,api',
        ]);

        $store = $this->store();
        $isFirst = ! $store->storeDomains()->exists();

        $store->storeDomains()->create([
            'hostname' => strtolower($validated['newHostname']),
            'type' => StoreDomainType::from($validated['newType']),
            'is_primary' => $isFirst,
            'tls_mode' => TlsMode::Managed,
        ]);

        $this->newHostname = '';
        $this->newType = 'storefront';
        $this->showAddModal = false;
        $this->toast('success', 'Domain added.');
    }

    public function removeDomain(int $domainId): void
    {
        Gate::authorize('updateSettings', $this->store());

        $domain = StoreDomain::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($domainId)
            ->firstOrFail();

        if ($domain->is_primary) {
            $this->toast('error', 'The primary domain cannot be removed.');

            return;
        }

        $domain->delete();
        $this->toast('success', 'Domain removed.');
    }

    public function setPrimary(int $domainId): void
    {
        Gate::authorize('updateSettings', $this->store());

        $store = $this->store();
        $domain = $store->storeDomains()->whereKey($domainId)->firstOrFail();

        $store->storeDomains()->update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);

        $this->toast('success', 'Primary domain updated.');
    }

    public function render()
    {
        return view('livewire.admin.settings.domains', [
            'domains' => $this->store()->storeDomains()->orderByDesc('is_primary')->orderBy('hostname')->get(),
        ]);
    }
}
