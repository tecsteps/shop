<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    #[Url]
    public string $tab = 'general';

    public string $name = '';

    public string $default_currency = 'USD';

    public string $default_locale = 'en';

    public string $timezone = 'UTC';

    public string $newDomain = '';

    public function mount(): void
    {
        $store = app('current_store');
        $this->name = (string) $store->name;
        $this->default_currency = (string) $store->default_currency;
        $this->default_locale = (string) $store->default_locale;
        $this->timezone = (string) $store->timezone;
    }

    public function saveGeneral(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'default_currency' => 'required|string|size:3',
            'default_locale' => 'required|string|max:10',
            'timezone' => 'required|string|max:50',
        ]);

        $store = app('current_store');
        Store::query()->whereKey($store->id)->update($data);

        session()->flash('success', 'General settings saved.');
    }

    public function addDomain(): void
    {
        $this->validate([
            'newDomain' => 'required|string|max:255|unique:store_domains,hostname',
        ]);

        $store = app('current_store');
        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => strtolower($this->newDomain),
            'type' => 'storefront',
            'is_primary' => false,
            'created_at' => now(),
        ]);
        $this->newDomain = '';
    }

    public function removeDomain(int $id): void
    {
        StoreDomain::query()->whereKey($id)->delete();
    }

    public function render()
    {
        $store = app('current_store');
        $domains = StoreDomain::query()->where('store_id', $store->id)->get();

        return view('livewire.admin.settings.index', [
            'store' => $store,
            'domains' => $domains,
        ]);
    }
}
