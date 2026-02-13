<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreDomain;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public string $activeTab = 'general';

    public string $storeName = '';

    public string $defaultCurrency = 'USD';

    public string $timezone = 'UTC';

    public string $newDomainHostname = '';

    public function mount(): void
    {
        if (app()->bound('current_store')) {
            $store = app('current_store');
            $this->storeName = $store->name;
            $this->defaultCurrency = $store->default_currency ?? 'USD';
            $this->timezone = $store->timezone ?? 'UTC';
        }
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:100'],
        ]);

        if (app()->bound('current_store')) {
            $store = app('current_store');
            $store->update([
                'name' => $this->storeName,
                'default_currency' => $this->defaultCurrency,
                'timezone' => $this->timezone,
            ]);

            session()->flash('message', 'Settings saved.');
        }
    }

    public function addDomain(): void
    {
        $this->validate([
            'newDomainHostname' => ['required', 'string', 'max:255'],
        ]);

        if (app()->bound('current_store')) {
            StoreDomain::create([
                'store_id' => app('current_store')->id,
                'hostname' => $this->newDomainHostname,
                'is_primary' => false,
            ]);

            $this->newDomainHostname = '';
        }
    }

    public function deleteDomain(int $domainId): void
    {
        if (app()->bound('current_store')) {
            StoreDomain::query()
                ->where('id', $domainId)
                ->where('store_id', app('current_store')->id)
                ->where('is_primary', false)
                ->delete();
        }
    }

    public function render(): View
    {
        $domains = [];
        if (app()->bound('current_store')) {
            $domains = StoreDomain::query()
                ->where('store_id', app('current_store')->id)
                ->get();
        }

        return view('livewire.admin.settings.index', [
            'domains' => $domains,
        ]);
    }
}
