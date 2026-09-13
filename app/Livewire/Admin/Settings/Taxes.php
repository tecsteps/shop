<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\TaxSetting;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Taxes extends Component
{
    use ResolvesAdminStore;

    public string $mode = 'manual';

    public bool $pricesIncludeTax = true;

    public string $provider = 'none';

    public string $providerApiKey = '';

    /** @var array<int, array{zone_name: string, rate_percentage: string}> */
    public array $manualRates = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', TaxSetting::class);

        $settings = $this->settings();
        $config = is_array($settings->config_json) ? $settings->config_json : [];

        $this->mode = $settings->mode->value;
        $this->pricesIncludeTax = (bool) $settings->prices_include_tax;
        $this->provider = $settings->provider->value;
        $this->providerApiKey = (string) ($config['provider_api_key'] ?? '');

        $this->manualRates = array_map(
            fn (array $entry): array => [
                'zone_name' => (string) ($entry['zone_name'] ?? $entry['name'] ?? $entry['country_code'] ?? ''),
                'rate_percentage' => number_format(((int) ($entry['rate'] ?? 0)) / 100, 2, '.', ''),
            ],
            array_values((array) ($config['tax_rates'] ?? []))
        );
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
        $settings = $this->settings();

        Gate::authorize('update', $settings);

        $validated = $this->validate([
            'mode' => 'required|in:manual,provider',
            'provider' => 'required|in:none,stripe_tax',
            'manualRates' => 'nullable|array',
            'manualRates.*.zone_name' => 'required|string|max:100',
            'manualRates.*.rate_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $config = is_array($settings->config_json) ? $settings->config_json : [];
        $config['provider_api_key'] = $this->providerApiKey;

        if ($validated['mode'] === 'manual') {
            $config['tax_rates'] = array_map(function (array $entry): array {
                $zoneName = trim($entry['zone_name']);

                return [
                    'zone_name' => $zoneName,
                    'country_code' => strlen($zoneName) === 2 ? strtoupper($zoneName) : '',
                    'name' => $zoneName,
                    'rate' => (int) round(((float) $entry['rate_percentage']) * 100),
                    'shipping_taxed' => true,
                ];
            }, array_values($validated['manualRates'] ?? []));
        }

        $settings->update([
            'mode' => TaxMode::from($validated['mode']),
            'provider' => TaxProvider::from($validated['provider']),
            'prices_include_tax' => $this->pricesIncludeTax,
            'config_json' => $config,
        ]);

        $this->toast('success', 'Tax settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.taxes')
            ->layout('livewire.admin.layout.app', ['title' => 'Tax settings']);
    }

    protected function settings(): TaxSetting
    {
        return TaxSetting::query()->firstOrCreate(
            ['store_id' => $this->store()->getKey()],
            [
                'mode' => TaxMode::Manual,
                'provider' => TaxProvider::None,
                'prices_include_tax' => true,
                'config_json' => [],
            ]
        );
    }
}
