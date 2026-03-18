<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public float $defaultRate = 0;

    public function mount(): void
    {
        $store = app('current_store');
        $settings = TaxSettings::where('store_id', $store->id)->first();

        if ($settings) {
            $this->mode = $settings->mode->value;
            $this->pricesIncludeTax = $settings->prices_include_tax;
            $this->defaultRate = $settings->config_json['default_rate'] ?? 0;
        }
    }

    public function save(): void
    {
        $this->validate([
            'mode' => ['required', 'in:manual,provider'],
            'defaultRate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $store = app('current_store');

        TaxSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->mode,
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => [
                    'default_rate' => (float) $this->defaultRate,
                ],
            ]
        );

        $this->dispatch('toast', type: 'success', message: __('Tax settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes');
    }
}
