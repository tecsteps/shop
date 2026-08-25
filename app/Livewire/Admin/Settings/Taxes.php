<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\TaxSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Taxes extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public string $mode = 'manual';

    public string $provider = 'none';

    public string $providerApiKey = '';

    public bool $pricesIncludeTax = false;

    /** @var list<array{zone_name: string, rate_percentage: ?float}> */
    public array $manualRates = [];

    public function mount(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $settings = TaxSettings::where('store_id', app('current_store')->id)->first();

        if ($settings) {
            $this->mode = $settings->mode;
            $this->provider = $settings->provider ?? 'none';
            $this->pricesIncludeTax = $settings->prices_include_tax;

            $config = $settings->config_json ?? [];
            $this->manualRates = $config['manual_rates'] ?? [['zone_name' => '', 'rate_percentage' => null]];
            $this->providerApiKey = (string) ($config['provider_api_key'] ?? '');
        } else {
            $this->manualRates = [['zone_name' => '', 'rate_percentage' => null]];
        }
    }

    public function addManualRate(): void
    {
        $this->manualRates[] = ['zone_name' => '', 'rate_percentage' => null];
    }

    public function removeManualRate(int $index): void
    {
        unset($this->manualRates[$index]);
        $this->manualRates = array_values($this->manualRates);
    }

    public function save(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $this->validate([
            'mode' => ['required', 'in:manual,provider'],
            'provider' => ['required', 'in:stripe_tax,none'],
            'pricesIncludeTax' => ['boolean'],
            'manualRates.*.zone_name' => ['nullable', 'string', 'max:255'],
            'manualRates.*.rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $manualRates = collect($this->manualRates)
            ->filter(fn ($rate) => trim((string) ($rate['zone_name'] ?? '')) !== '')
            ->map(fn ($rate) => [
                'zone_name' => trim((string) $rate['zone_name']),
                'rate_percentage' => $rate['rate_percentage'] !== null && $rate['rate_percentage'] !== ''
                    ? (float) $rate['rate_percentage']
                    : null,
            ])
            ->values()
            ->all();

        TaxSettings::updateOrCreate(
            ['store_id' => app('current_store')->id],
            [
                'mode' => $this->mode,
                'provider' => $this->provider,
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => [
                    'manual_rates' => $this->mode === 'manual' ? $manualRates : [],
                    'provider_api_key' => $this->providerApiKey,
                ],
            ]
        );

        $this->toast('Tax settings saved');
    }

    public function render()
    {
        return view('livewire.admin.settings.taxes');
    }
}
