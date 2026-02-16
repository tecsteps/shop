<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Settings'])]
class Index extends Component
{
    public string $store_name = '';

    public string $store_email = '';

    public string $timezone = 'UTC';

    public string $currency = 'USD';

    public string $weight_unit = 'g';

    public function mount(): void
    {
        $store = app('current_store');
        $settings = $store->settings;

        if ($settings) {
            $this->store_name = $settings->store_name ?? $store->name;
            $this->store_email = $settings->store_email ?? '';
            $this->timezone = $settings->timezone ?? 'UTC';
            $this->currency = $settings->currency ?? $store->currency ?? 'USD';
            $this->weight_unit = $settings->weight_unit ?? 'g';
        } else {
            $this->store_name = $store->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_email' => ['nullable', 'email'],
        ]);

        $store = app('current_store');

        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'store_name' => $this->store_name,
                'store_email' => $this->store_email,
                'timezone' => $this->timezone,
                'currency' => $this->currency,
                'weight_unit' => $this->weight_unit,
            ]
        );

        session()->flash('success', 'Settings saved.');
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.index');
    }
}
