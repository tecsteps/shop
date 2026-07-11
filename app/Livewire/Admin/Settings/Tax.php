<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\TaxSettings;
use Illuminate\Support\Facades\Gate;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Tax extends AdminComponent
{
    public string $mode = 'manual';

    public string $provider = 'none';

    public bool $pricesIncludeTax = false;

    public string $defaultRate = '0';

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->currentStore());
        $settings = TaxSettings::query()->firstOrCreate(['store_id' => $this->currentStore()->getKey()]);
        $this->mode = $settings->mode->value;
        $this->provider = $settings->provider;
        $this->pricesIncludeTax = $settings->prices_include_tax;
        $this->defaultRate = (string) (($settings->config_json['default_rate_bps'] ?? 0) / 100);
    }

    public function save(): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
        $validated = $this->validate(['mode' => ['required', 'in:manual,provider'], 'provider' => ['required', 'string', 'max:50'], 'pricesIncludeTax' => ['boolean'], 'defaultRate' => ['required', 'numeric', 'between:0,100']]);
        TaxSettings::query()->updateOrCreate(['store_id' => $this->currentStore()->getKey()], ['mode' => $validated['mode'], 'provider' => $validated['provider'], 'prices_include_tax' => $validated['pricesIncludeTax'], 'config_json' => ['default_rate_bps' => (int) round((float) $validated['defaultRate'] * 100)]]);
        $this->toast('Tax settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.tax');
    }
}
