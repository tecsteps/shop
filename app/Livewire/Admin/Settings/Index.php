<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public string $storeName = '';

    public string $defaultCurrency = '';

    public string $timezone = '';

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

    public function render(): View
    {
        return view('livewire.admin.settings.index');
    }
}
