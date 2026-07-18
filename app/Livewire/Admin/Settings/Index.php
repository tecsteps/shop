<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Settings')]
class Index extends Component
{
    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'USD';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public function mount(): void
    {
        $store = app('current_store');
        Gate::authorize('update', $store);
        $this->fill(['storeName' => $store->name, 'storeHandle' => $store->handle, 'defaultCurrency' => $store->default_currency, 'defaultLocale' => $store->default_locale, 'timezone' => $store->timezone]);
    }

    public function save(): void
    {
        $store = app('current_store');
        Gate::authorize('update', $store);
        $validated = $this->validate(['storeName' => ['required', 'string', 'max:255'], 'defaultCurrency' => ['required', 'string', 'size:3'], 'defaultLocale' => ['required', 'string', 'max:10'], 'timezone' => ['required', 'timezone']]);
        $store->update(['name' => $validated['storeName'], 'default_currency' => strtoupper($validated['defaultCurrency']), 'default_locale' => $validated['defaultLocale'], 'timezone' => $validated['timezone']]);
        $this->dispatch('toast', type: 'success', message: 'Settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.index', ['timezones' => timezone_identifiers_list()]);
    }
}
