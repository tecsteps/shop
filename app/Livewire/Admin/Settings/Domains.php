<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Domains extends AdminComponent
{
    public string $hostname = '';

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->currentStore());
    }

    public function addDomain(): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
        $validated = $this->validate(['hostname' => ['required', 'lowercase', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', 'unique:store_domains,hostname']]);
        StoreDomain::create(['store_id' => $this->currentStore()->getKey(), 'hostname' => $validated['hostname'], 'type' => 'storefront', 'is_primary' => false]);
        $this->reset('hostname');
        $this->toast('Domain added.');
    }

    public function makePrimary(int $id): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
        $domain = StoreDomain::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        StoreDomain::query()->where('store_id', $this->currentStore()->getKey())->update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);
        $this->toast('Primary domain updated.');
    }

    public function removeDomain(int $id): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
        $domain = StoreDomain::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        abort_if($domain->is_primary, 422);
        $domain->delete();
        $this->toast('Domain removed.');
    }

    #[Computed]
    public function domains()
    {
        return StoreDomain::query()->where('store_id', $this->currentStore()->getKey())->orderByDesc('is_primary')->get();
    }

    public function render()
    {
        return view('livewire.admin.settings.domains');
    }
}
