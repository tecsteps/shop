<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Tax settings')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public string $provider = 'none';

    public array $manualRates = [];

    public function mount(): void
    {
        Gate::authorize('update', app('current_store'));
        $settings = TaxSettings::query()->find(app('current_store')->id);
        if ($settings) {
            $this->fill(['mode' => $settings->mode->value, 'pricesIncludeTax' => $settings->prices_include_tax, 'provider' => $settings->provider->value, 'manualRates' => $settings->config_json['tax_rates'] ?? []]);
        }
    }

    public function addManualRate(): void
    {
        $this->manualRates[] = ['country_code' => '', 'rate' => 0, 'name' => '', 'shipping_taxed' => true];
    }

    public function removeManualRate(int $index): void
    {
        unset($this->manualRates[$index]);
        $this->manualRates = array_values($this->manualRates);
    }

    public function save(): void
    {
        Gate::authorize('update', app('current_store'));
        $validated = $this->validate(['mode' => ['required', Rule::in(['manual', 'provider'])], 'provider' => ['required', Rule::in(['none', 'stripe_tax'])], 'pricesIncludeTax' => ['boolean'], 'manualRates' => ['array']]);
        TaxSettings::query()->updateOrCreate(['store_id' => app('current_store')->id], ['mode' => $validated['mode'], 'provider' => $validated['provider'], 'prices_include_tax' => $validated['pricesIncludeTax'], 'config_json' => ['tax_rates' => array_values($this->manualRates)]]);
        $this->dispatch('toast', type: 'success', message: 'Tax settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes');
    }
}
