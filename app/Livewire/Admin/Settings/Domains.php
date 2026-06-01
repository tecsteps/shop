<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreDomainType;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Store domains management (a tab within Settings). Lists domains and supports
 * add / remove / set-primary. Restricted to owners and admins.
 */
class Domains extends Component
{
    use BindsCurrentStore;

    public bool $showAddModal = false;

    public string $newHostname = '';

    public string $newType = 'storefront';

    public function mount(): void
    {
        $this->guard();
    }

    private function guard(): void
    {
        if (! Gate::allows('manage-store-settings')) {
            abort(403);
        }
    }

    public function getDomainsProperty()
    {
        return app('current_store')->domains()->orderByDesc('is_primary')->get();
    }

    public function addDomain(): void
    {
        $this->guard();

        $this->validate([
            'newHostname' => ['required', 'string', 'max:255', Rule::unique('store_domains', 'hostname')],
            'newType' => ['required', Rule::in(['storefront', 'admin', 'api'])],
        ]);

        app('current_store')->domains()->create([
            'hostname' => $this->newHostname,
            'type' => $this->newType,
            'is_primary' => $this->domains->isEmpty(),
            'tls_mode' => 'managed',
        ]);

        $this->reset('newHostname', 'newType', 'showAddModal');
        $this->newType = 'storefront';

        $this->dispatch('toast', type: 'success', message: __('Domain added'));
    }

    public function removeDomain(int $domainId): void
    {
        $this->guard();

        StoreDomain::query()->where('store_id', app('current_store')->id)->whereKey($domainId)->delete();

        $this->dispatch('toast', type: 'success', message: __('Domain removed'));
    }

    public function setPrimary(int $domainId): void
    {
        $this->guard();

        $store = app('current_store');
        $store->domains()->update(['is_primary' => false]);
        $store->domains()->whereKey($domainId)->update(['is_primary' => true]);

        $this->dispatch('toast', type: 'success', message: __('Primary domain updated'));
    }

    public function render()
    {
        return view('livewire.admin.settings.domains');
    }

    /**
     * @return array<int, string>
     */
    public function domainTypes(): array
    {
        return array_map(fn (StoreDomainType $t): string => $t->value, StoreDomainType::cases());
    }
}
