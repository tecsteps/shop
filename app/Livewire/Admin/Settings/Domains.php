<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Domains extends Component
{
    public string $newHostname = '';

    public string $newType = 'storefront';

    public bool $showAddModal = false;

    /** @var array<string, list<string>> */
    protected array $rules = [
        'newHostname' => ['required', 'string', 'max:255'],
        'newType' => ['required', 'string', 'in:storefront,admin,api'],
    ];

    public function addDomain(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $store->domains()->create([
            'hostname' => $this->newHostname,
            'type' => StoreDomainType::from($this->newType),
            'is_primary' => $store->domains()->count() === 0,
        ]);

        $this->reset('newHostname', 'newType', 'showAddModal');
        $this->dispatch('toast', type: 'success', message: 'Domain added successfully.');
    }

    public function removeDomain(int $domainId): void
    {
        /** @var Store $store */
        $store = app('current_store');
        $domain = $store->domains()->findOrFail($domainId);

        if ($domain->is_primary) {
            $this->dispatch('toast', type: 'error', message: 'Cannot remove the primary domain.');

            return;
        }

        $domain->delete();
        $this->dispatch('toast', type: 'success', message: 'Domain removed successfully.');
    }

    public function setPrimary(int $domainId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $store->domains()->update(['is_primary' => false]);
        $store->domains()->where('id', $domainId)->update(['is_primary' => true]);

        $this->dispatch('toast', type: 'success', message: 'Primary domain updated.');
    }

    /**
     * @return Collection<int, StoreDomain>
     */
    public function getDomains(): Collection
    {
        /** @var Store $store */
        $store = app('current_store');

        return $store->domains()->orderByDesc('is_primary')->get();
    }

    public function render()
    {
        return view('livewire.admin.settings.domains', [
            'domains' => $this->getDomains(),
        ]);
    }
}
