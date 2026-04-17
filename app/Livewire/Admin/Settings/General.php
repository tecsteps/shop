<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class General extends Component
{
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'USD';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public function mount(): void
    {
        $store = app('current_store');
        $this->authorize('viewSettings', $store);
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        $store = app('current_store');
        $this->authorize('updateSettings', $store);

        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:64'],
        ]);

        /** @var Store $model */
        $model = Store::query()->findOrFail($store->getKey());
        $model->name = $this->storeName;
        $model->default_currency = strtoupper($this->defaultCurrency);
        $model->default_locale = $this->defaultLocale;
        $model->timezone = $this->timezone;
        $model->save();

        session()->flash('status', 'Store settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.general');
    }
}
