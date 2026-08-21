<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;

class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public string $provider = 'none';

    public string $providerApiKey = '';

    public bool $providerKeyConfigured = false;

    /** @var array<int, array{zone_name: string, rate_percentage: string}> */
    public array $manualRates = [];

    public string $message = '';

    public function mount(): void
    {
        $this->authorize('update', app('current_store'));
        $settings = TaxSettings::query()->first();
        $storedMode = (string) ($settings?->mode ?? 'manual');
        $this->mode = in_array($storedMode, ['manual', 'provider'], true) ? $storedMode : 'manual';
        $this->pricesIncludeTax = (bool) ($settings?->prices_include_tax ?? $storedMode === 'inclusive');
        $providerConfig = $settings?->provider_config_json ?? [];
        $this->provider = (string) ($settings?->provider ?? $providerConfig['provider'] ?? 'none');
        $this->providerKeyConfigured = isset($providerConfig['api_key_encrypted']) || isset($providerConfig['api_key']);
        $this->manualRates = collect($settings?->rates_json ?? [])
            ->map(fn (int|float|string $rate, string $zone): array => ['zone_name' => $zone, 'rate_percentage' => number_format(((float) $rate) / 100, 2, '.', '')])
            ->values()
            ->all();

        if ($this->manualRates === []) {
            $this->manualRates[] = ['zone_name' => 'DE', 'rate_percentage' => '19.00'];
        }
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

    public function save(): void
    {
        $this->authorize('update', app('current_store'));
        $data = $this->validate([
            'mode' => ['required', 'in:manual,provider'],
            'pricesIncludeTax' => ['boolean'],
            'provider' => ['required', 'in:none,stripe_tax'],
            'providerApiKey' => ['nullable', 'string', 'max:500'],
            'manualRates' => ['array'],
            'manualRates.*.zone_name' => ['required', 'string', 'max:50'],
            'manualRates.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $rates = collect($data['manualRates'])
            ->mapWithKeys(fn (array $rate): array => [strtoupper(trim($rate['zone_name'])) => (int) round(((float) $rate['rate_percentage']) * 100)])
            ->all();
        $providerConfig = ['provider' => $data['provider']];
        $existingApiKey = TaxSettings::query()->first()?->provider_config_json['api_key_encrypted'] ?? null;

        if ($data['providerApiKey'] !== '') {
            $providerConfig['api_key_encrypted'] = Crypt::encryptString($data['providerApiKey']);
        } elseif ($existingApiKey !== null) {
            $providerConfig['api_key_encrypted'] = $existingApiKey;
        }

        TaxSettings::updateOrCreate(
            ['store_id' => app('current_store')->getKey()],
            [
                'mode' => $data['mode'],
                'provider' => $data['provider'],
                'prices_include_tax' => (bool) $data['pricesIncludeTax'],
                'default_rate_basis_points' => (int) (array_values($rates)[0] ?? 0),
                'rates_json' => $rates,
                'provider_config_json' => $providerConfig,
            ],
        );
        $this->providerApiKey = '';
        $this->providerKeyConfigured = isset($providerConfig['api_key_encrypted']);
        $this->message = 'Tax settings saved.';
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.taxes')->layout('layouts.admin');
    }
}
