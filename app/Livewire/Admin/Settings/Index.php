<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreDomain;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public string $storeName = '';

    public string $defaultCurrency = '';

    public string $timezone = '';

    public string $newDomainHostname = '';

    public function mount(): void
    {
        $store = app('current_store');
        $this->storeName = $store->name;
        $this->defaultCurrency = $store->default_currency ?? 'EUR';
        $this->timezone = $store->timezone ?? 'UTC';
    }

    public function save(): void
    {
        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'max:3'],
            'timezone' => ['required', 'string', 'max:64'],
        ]);

        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'timezone' => $this->timezone,
        ]);

        $this->dispatch('toast', type: 'success', message: __('Settings saved.'));
    }

    public function addDomain(): void
    {
        $this->validate([
            'newDomainHostname' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => $this->newDomainHostname,
            'type' => 'storefront',
            'is_primary' => $store->domains()->count() === 0,
        ]);

        $this->newDomainHostname = '';
        $this->dispatch('toast', type: 'success', message: __('Domain added.'));
    }

    public function removeDomain(int $domainId): void
    {
        $store = app('current_store');
        $store->domains()->where('id', $domainId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Domain removed.'));
    }

    public function render(): View
    {
        $store = app('current_store');

        return view('livewire.admin.settings.index', [
            'domains' => $store->domains()->orderByDesc('is_primary')->get(),
        ]);
    }
}
