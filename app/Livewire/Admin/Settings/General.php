<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class General extends Component
{
    #[Validate('required|string|max:255')]
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public string $activeTab = 'general';

    public function mount(): void
    {
        $store = Store::withoutGlobalScopes()->find(session('store_id'));
        if ($store) {
            $this->storeName = $store->name;
            $this->storeHandle = $store->handle;
            $this->defaultCurrency = $store->default_currency ?? 'EUR';
            $this->defaultLocale = $store->default_locale ?? 'en';
            $this->timezone = $store->timezone ?? 'UTC';
        }
    }

    public function save(): void
    {
        $this->validate();

        $store = Store::withoutGlobalScopes()->find(session('store_id'));
        if ($store) {
            $store->update([
                'name' => $this->storeName,
                'default_currency' => $this->defaultCurrency,
                'default_locale' => $this->defaultLocale,
                'timezone' => $this->timezone,
            ]);

            session(['current_store' => $store->fresh()]);
        }

        $this->dispatch('toast', type: 'success', message: 'Settings saved.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.general');
    }
}
