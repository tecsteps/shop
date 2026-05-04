<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Taxes extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public string $provider = 'none';

    public string $providerApiKey = '';

    /**
     * @var array<int, array{country: string, name: string, rate_percentage: string}>
     */
    public array $manualRates = [];

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $this->storeId = $store->getKey();
        $this->fillFromSettings($this->taxSettings($store));
    }

    public function addManualRate(): void
    {
        $this->manualRates[] = [
            'country' => '',
            'name' => 'Tax',
            'rate_percentage' => '0.00',
        ];
    }

    public function removeManualRate(int $index): void
    {
        unset($this->manualRates[$index]);

        $this->manualRates = array_values($this->manualRates);
    }

    public function save(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'mode' => ['required', Rule::in(array_column(TaxMode::cases(), 'value'))],
            'pricesIncludeTax' => ['boolean'],
            'provider' => ['required', Rule::in(['none', 'stripe_tax'])],
            'providerApiKey' => ['nullable', 'string', 'max:255'],
            'manualRates' => ['array'],
            'manualRates.*.country' => ['required', 'string', 'size:2'],
            'manualRates.*.name' => ['required', 'string', 'max:50'],
            'manualRates.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        TaxSettings::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->getKey()],
            [
                'mode' => TaxMode::from($validated['mode']),
                'provider' => $this->mode === TaxMode::Provider->value ? $this->provider : 'none',
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => [
                    'name' => 'Tax',
                    'default_rate_bps' => $this->firstRateBasisPoints(),
                    'shipping_taxable' => true,
                    'provider_api_key' => $this->mode === TaxMode::Provider->value ? $this->providerApiKey : null,
                    'rates' => collect($this->manualRates)
                        ->map(fn (array $rate): array => [
                            'country' => strtoupper($rate['country']),
                            'rate_bps' => (int) round(((float) $rate['rate_percentage']) * 100),
                            'name' => $rate['name'],
                        ])
                        ->values()
                        ->all(),
                ],
            ],
        );

        session()->flash('status', 'Tax settings saved');
        $this->dispatch('toast', type: 'success', message: __('Tax settings saved'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.taxes')
            ->layout('layouts.app', [
                'title' => __('Taxes'),
            ]);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->whereKey($this->storeId)->firstOrFail();
    }

    private function taxSettings(Store $store): TaxSettings
    {
        return TaxSettings::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->getKey()],
            [
                'mode' => TaxMode::Manual,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [
                    'name' => 'Tax',
                    'default_rate_bps' => 0,
                    'shipping_taxable' => true,
                    'rates' => [],
                ],
            ],
        );
    }

    private function fillFromSettings(TaxSettings $settings): void
    {
        $this->mode = $settings->mode->value;
        $this->pricesIncludeTax = $settings->prices_include_tax;
        $this->provider = $settings->provider;
        $this->providerApiKey = (string) data_get($settings->config_json, 'provider_api_key', '');
        $this->manualRates = collect(data_get($settings->config_json, 'rates', []))
            ->map(fn (array $rate): array => [
                'country' => (string) data_get($rate, 'country', ''),
                'name' => (string) data_get($rate, 'name', 'Tax'),
                'rate_percentage' => number_format(((int) data_get($rate, 'rate_bps', 0)) / 100, 2, '.', ''),
            ])
            ->values()
            ->all();

        if ($this->manualRates === []) {
            $this->addManualRate();
        }
    }

    private function firstRateBasisPoints(): int
    {
        $first = $this->manualRates[0]['rate_percentage'] ?? '0';

        return (int) round(((float) $first) * 100);
    }
}
