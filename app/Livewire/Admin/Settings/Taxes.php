<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSetting;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Taxes extends Component
{
    public bool $pricesIncludeTax = true;

    public int $defaultRate = 1900;

    public string $countryCode = 'DE';

    public int $countryRate = 1900;

    public function mount(): void
    {
        Gate::authorize('manage-shipping');
        $taxes = TaxSetting::query()->where('store_id', app('current_store')->id)->first();
        $this->pricesIncludeTax = $taxes?->prices_include_tax ?? true;
        $this->defaultRate = $taxes?->defaultTaxRateForCalculator() ?: 1900;
        $rates = $taxes?->taxRatesForCalculator() ?? [];
        $this->countryCode = array_key_first($rates) ?? 'DE';
        $this->countryRate = (int) ($rates[$this->countryCode] ?? $this->defaultRate);
    }

    public function save(): void
    {
        Gate::authorize('manage-shipping');
        $this->validate([
            'pricesIncludeTax' => ['boolean'],
            'defaultRate' => ['required', 'integer', 'min:0', 'max:10000'],
            'countryCode' => ['required', 'string', 'size:2'],
            'countryRate' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        $taxes = TaxSetting::query()->where('store_id', app('current_store')->id)->first();
        $rates = $taxes?->taxRatesForCalculator() ?? [];
        $rates[strtoupper($this->countryCode)] = $this->countryRate;
        TaxSetting::updateOrCreate(['store_id' => app('current_store')->id], [
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => $this->pricesIncludeTax,
            'config_json' => [
                'default_tax_rate' => $this->defaultRate,
                'tax_rates' => collect($rates)->map(static fn (int $rate, string $countryCode): array => [
                    'country_code' => $countryCode,
                    'rate' => $rate,
                ])->values()->all(),
                'fallback' => $taxes?->config_json['fallback'] ?? 'allow',
            ],
            'default_rate' => $this->defaultRate,
            'rates_json' => $rates,
        ]);
        session()->flash('status', 'Tax settings saved.');
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.taxes')->layout('layouts.admin');
    }
}
