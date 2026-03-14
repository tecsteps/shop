<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use Livewire\Component;

class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public string $provider = '';

    public string $providerApiKey = '';

    /** @var array<int, array{zone_name: string, rate_percentage: string}> */
    public array $manualRates = [];

    public function mount(): void
    {
        /** @var Store $store */
        $store = app('current_store');
        $taxSettings = TaxSettings::where('store_id', $store->id)->first();

        if ($taxSettings) {
            $this->mode = $taxSettings->mode->value;
            $this->pricesIncludeTax = $taxSettings->prices_include_tax;
            $config = $taxSettings->config_json ?? [];
            $this->provider = $config['provider'] ?? '';
            $this->providerApiKey = $config['api_key'] ?? '';
            $this->manualRates = $config['manual_rates'] ?? [];
        }

        if (empty($this->manualRates)) {
            $this->manualRates = [['zone_name' => '', 'rate_percentage' => '']];
        }
    }

    public function save(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $config = [];
        if ($this->mode === 'manual') {
            $config['manual_rates'] = array_values(array_filter($this->manualRates, function ($rate) {
                return ! empty($rate['zone_name']) || ! empty($rate['rate_percentage']);
            }));
        } else {
            $config['provider'] = $this->provider;
            $config['api_key'] = $this->providerApiKey;
        }

        TaxSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => TaxMode::from($this->mode),
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => $config,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Tax settings saved.');
    }

    public function addManualRate(): void
    {
        $this->manualRates[] = ['zone_name' => '', 'rate_percentage' => ''];
    }

    public function removeManualRate(int $index): void
    {
        unset($this->manualRates[$index]);
        $this->manualRates = array_values($this->manualRates);
    }

    public function render()
    {
        return view('livewire.admin.settings.taxes')
            ->layout('layouts.admin', ['title' => 'Tax Settings']);
    }
}
