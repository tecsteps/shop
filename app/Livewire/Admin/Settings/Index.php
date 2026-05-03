<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\StoreStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public string $name = '';

    public string $defaultCurrency = '';

    public string $defaultLocale = '';

    public string $timezone = '';

    public string $status = 'active';

    public function mount(): void
    {
        $store = $this->currentStore();
        $this->name = $store->name;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
        $this->status = $store->status->value;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_map(fn (StoreStatus $status): string => $status->value, StoreStatus::cases()))],
        ]);

        $this->currentStore()->forceFill([
            'name' => $validated['name'],
            'default_currency' => strtoupper($validated['defaultCurrency']),
            'default_locale' => $validated['defaultLocale'],
            'timezone' => $validated['timezone'],
            'status' => $validated['status'],
        ])->save();

        $this->notify('Store settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.index', [
            'statuses' => StoreStatus::cases(),
            'domains' => $this->currentStore()->domains()->orderBy('hostname')->get(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Settings',
        ]);
    }
}
