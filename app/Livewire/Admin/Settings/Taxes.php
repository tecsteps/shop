<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\TaxSettings;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Taxes extends Component
{
    use UsesAdminStore;

    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public int $manualRate = 1900;

    public function mount(): void
    {
        $settings = $this->settings();
        $this->mode = $settings->mode->value;
        $this->pricesIncludeTax = $settings->prices_include_tax;
        $this->manualRate = (int) data_get($settings->config_json, 'manual_rate_bps', 1900);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'mode' => ['required', Rule::in(array_map(fn (TaxMode $mode): string => $mode->value, TaxMode::cases()))],
            'pricesIncludeTax' => ['bool'],
            'manualRate' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $this->settings()->forceFill([
            'mode' => $validated['mode'],
            'provider' => $validated['mode'] === TaxMode::Provider->value ? 'mock' : 'none',
            'prices_include_tax' => $validated['pricesIncludeTax'],
            'config_json' => ['manual_rate_bps' => $validated['manualRate']],
        ])->save();

        $this->notify('Tax settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes', [
            'modes' => TaxMode::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Taxes',
        ]);
    }

    private function settings(): TaxSettings
    {
        return TaxSettings::query()->firstOrCreate(
            ['store_id' => $this->currentStore()->id],
            ['config_json' => ['manual_rate_bps' => 1900]],
        );
    }
}
