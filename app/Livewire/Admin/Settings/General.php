<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('General Settings')]
class General extends Component
{
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public function mount(): void
    {
        $store = app('current_store');
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency ?? 'EUR';
        $this->defaultLocale = $store->default_locale ?? 'en';
        $this->timezone = $store->timezone ?? 'UTC';
    }

    public function save(): void
    {
        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'max:3'],
            'defaultLocale' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'string', 'max:50'],
        ]);

        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.general', [
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }
}
