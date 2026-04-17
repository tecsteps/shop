<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public float $defaultRate = 0.19;

    public bool $pricesIncludeTax = false;

    public function mount(): void
    {
        $settings = TaxSettings::find(app('current_store')->id);
        if ($settings) {
            $this->mode = $settings->mode->value;
            $this->defaultRate = (float) ($settings->config_json['default_rate'] ?? 0.19);
            $this->pricesIncludeTax = $settings->prices_include_tax;
        }
    }

    public function save(): void
    {
        TaxSettings::updateOrCreate(
            ['store_id' => app('current_store')->id],
            [
                'mode' => $this->mode,
                'provider' => null,
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => ['default_rate' => $this->defaultRate],
            ],
        );

        session()->flash('success', 'Tax settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.taxes')->title('Taxes');
    }
}
