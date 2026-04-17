<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public int $defaultRate = 0;

    public function mount(): void
    {
        if (app()->bound('current_store')) {
            /** @var \App\Models\Store $store */
            $store = app('current_store');
            $taxSettings = TaxSettings::query()
                ->where('store_id', $store->id)
                ->first();

            if ($taxSettings) {
                /** @var \App\Enums\TaxMode $taxMode */
                $taxMode = $taxSettings->mode;
                $this->mode = $taxMode->value;
                $this->pricesIncludeTax = (bool) $taxSettings->prices_include_tax;
                /** @var array<string, int> $configJson */
                $configJson = $taxSettings->config_json ?? [];
                $this->defaultRate = $configJson['default_rate'] ?? 0;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'mode' => ['required', 'string'],
            'defaultRate' => ['required', 'integer', 'min:0'],
        ]);

        if (app()->bound('current_store')) {
            /** @var \App\Models\Store $store */
            $store = app('current_store');
            TaxSettings::query()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'mode' => $this->mode,
                    'prices_include_tax' => $this->pricesIncludeTax,
                    'config_json' => ['default_rate' => $this->defaultRate],
                ],
            );

            session()->flash('message', 'Tax settings saved.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes');
    }
}
