<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Settings')]
class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $storeName = '';

    public string $storeHandle = '';

    #[Validate('required|string|max:3')]
    public string $defaultCurrency = '';

    #[Validate('required|string|max:10')]
    public string $defaultLocale = '';

    #[Validate('required|string|max:64')]
    public string $timezone = '';

    public function mount(): void
    {
        $this->authorize('manage-store-settings');

        /** @var Store $store */
        $store = app('current_store');
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        $this->validate();
        $this->authorize('manage-store-settings');

        /** @var Store $store */
        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Settings saved.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.settings.index');
    }
}
