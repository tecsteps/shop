<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
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
        $settings = TaxSettings::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->first();

        if ($settings) {
            $this->mode = $settings->mode ?? 'manual';
            $this->pricesIncludeTax = $settings->prices_include_tax ?? false;
            $this->provider = $settings->provider ?? '';
            $this->providerApiKey = $settings->provider_api_key ?? '';
            $this->manualRates = $settings->manual_rates ?? [];
        }
    }

    public function addManualRate(): void
    {
        $this->manualRates[] = ['zone_name' => '', 'rate_percentage' => '0'];
    }

    public function removeManualRate(int $index): void
    {
        unset($this->manualRates[$index]);
        $this->manualRates = array_values($this->manualRates);
    }

    public function save(): void
    {
        TaxSettings::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => session('store_id')],
            [
                'mode' => $this->mode,
                'prices_include_tax' => $this->pricesIncludeTax,
                'provider' => $this->provider ?: null,
                'provider_api_key' => $this->providerApiKey ?: null,
                'manual_rates' => $this->manualRates,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Tax settings saved.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.taxes');
    }
}
