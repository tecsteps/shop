<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\TaxSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tax settings: manual rates vs. an external provider, the prices-include-tax
 * toggle, and per-zone manual rate rows. The first manual rate's percentage is
 * also stored as basis points in `config_json.default_rate` so the tax
 * calculator has a default. Owners and admins only.
 */
#[Layout('livewire.admin.layout.app')]
class Taxes extends Component
{
    use BindsCurrentStore;

    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public string $provider = '';

    public string $providerApiKey = '';

    /** @var array<int, array{zone_name: string, rate_percentage: string}> */
    public array $manualRates = [];

    public function mount(): void
    {
        $this->guard();

        $settings = TaxSettings::query()->find(app('current_store')->id);

        if ($settings !== null) {
            $this->mode = $settings->mode->value;
            $this->pricesIncludeTax = (bool) $settings->prices_include_tax;
            $this->provider = (string) $settings->provider;
            $config = $settings->config_json ?? [];
            $this->providerApiKey = (string) ($config['api_key'] ?? '');
            $this->manualRates = collect($config['manual_rates'] ?? [])
                ->map(fn (array $r): array => [
                    'zone_name' => (string) ($r['zone_name'] ?? ''),
                    'rate_percentage' => isset($r['rate_basis_points']) ? number_format($r['rate_basis_points'] / 100, 2, '.', '') : '',
                ])->all();
        }

        if ($this->manualRates === []) {
            $this->manualRates = [['zone_name' => '', 'rate_percentage' => '']];
        }
    }

    private function guard(): void
    {
        if (! Gate::allows('manage-taxes')) {
            abort(403);
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
        $this->guard();

        $this->validate([
            'mode' => ['required', Rule::in(['manual', 'provider'])],
            'manualRates.*.rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $rates = collect($this->manualRates)
            ->filter(fn (array $r): bool => trim($r['zone_name']) !== '' || $r['rate_percentage'] !== '')
            ->map(fn (array $r): array => [
                'zone_name' => trim($r['zone_name']),
                'rate_basis_points' => (int) round(((float) $r['rate_percentage']) * 100),
            ])
            ->values()
            ->all();

        $config = [
            'manual_rates' => $rates,
            'default_rate' => $rates[0]['rate_basis_points'] ?? 0,
            'api_key' => $this->providerApiKey,
        ];

        TaxSettings::query()->updateOrCreate(
            ['store_id' => app('current_store')->id],
            [
                'mode' => $this->mode,
                'provider' => $this->mode === 'provider' ? ($this->provider ?: 'none') : 'none',
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => $config,
            ],
        );

        $this->dispatch('toast', type: 'success', message: __('Settings saved'));
    }

    public function render()
    {
        return view('livewire.admin.settings.taxes');
    }
}
