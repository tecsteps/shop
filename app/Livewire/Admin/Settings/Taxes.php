<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Tax Settings')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    /** @var array<int, array{name: string, rate: string}> */
    public array $rates = [];

    public function mount(): void
    {
        $store = app('current_store');
        $taxSettings = TaxSettings::where('store_id', $store->id)->first();

        if ($taxSettings) {
            $this->mode = $taxSettings->mode->value;
            $this->pricesIncludeTax = $taxSettings->prices_include_tax;
            $this->rates = $taxSettings->config_json['rates'] ?? [];
        }
    }

    public function addRate(): void
    {
        $this->rates[] = ['name' => '', 'rate' => ''];
    }

    public function removeRate(int $index): void
    {
        unset($this->rates[$index]);
        $this->rates = array_values($this->rates);
    }

    public function save(): void
    {
        $store = app('current_store');

        TaxSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->mode,
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => ['rates' => $this->rates],
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Tax settings saved.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.settings.taxes');
    }
}
