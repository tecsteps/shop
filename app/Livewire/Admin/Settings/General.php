<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class General extends Component
{
    public string $storeName = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'Europe/Berlin';

    public function mount(): void
    {
        $store = app('current_store');
        $this->storeName = $store->name;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        Store::query()->where('id', app('current_store')->id)->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        session()->flash('success', 'General settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.general')->title('General settings');
    }
}
