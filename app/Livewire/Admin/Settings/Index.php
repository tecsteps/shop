<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $storeName = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'USD';

    #[Validate('required|string')]
    public string $timezone = 'UTC';

    public function mount(): void
    {
        /** @var Store $store */
        $store = app('current_store');
        $this->storeName = $store->name;
        $this->currency = $store->default_currency;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->currency,
            'timezone' => $this->timezone,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Settings saved successfully.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.index');
    }
}
