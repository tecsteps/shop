<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Taxes extends Component
{
    public string $mode = 'manual';

    public string $provider = 'none';

    public bool $pricesIncludeTax = false;

    public ?int $defaultRateBps = null;

    public string $fallback = 'block';

    /** @var array<int|string, int|string|null> Zone id => rate in basis points */
    public array $zoneRates = [];

    public function mount(): void
    {
        Gate::authorize('manage-taxes');

        /** @var Store $store */
        $store = app('current_store');

        $settings = $store->id !== null ? TaxSettings::query()->find($store->id) : null;
        $config = $settings?->config_json ?? [];

        $this->mode = $settings?->mode->value ?? 'manual';
        $this->provider = $settings?->provider ?? 'none';
        $this->pricesIncludeTax = $settings?->prices_include_tax ?? false;
        $this->defaultRateBps = isset($config['default_rate_bps']) ? (int) $config['default_rate_bps'] : null;
        $this->fallback = (string) ($config['fallback'] ?? 'block');
        $this->zoneRates = array_map('intval', $config['zone_rates'] ?? []);
    }

    /**
     * Persist tax settings (spec 03 §11.4, spec 05 §8).
     */
    public function save(): void
    {
        Gate::authorize('manage-taxes');

        $this->normalizeInputs();

        $validated = $this->validate([
            'mode' => ['required', Rule::in(['manual', 'provider'])],
            'provider' => ['required', Rule::in(['none', 'stripe_tax'])],
            'pricesIncludeTax' => ['boolean'],
            'defaultRateBps' => ['required', 'integer', 'min:0', 'max:10000'],
            'fallback' => ['required', Rule::in(['block', 'allow'])],
            'zoneRates' => ['array'],
            'zoneRates.*' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $zoneRates = collect($validated['zoneRates'] ?? [])
            ->filter(fn ($rate): bool => $rate !== null && $rate !== '')
            ->map(fn ($rate): int => (int) $rate)
            ->all();

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $validated['mode'],
                'provider' => $validated['mode'] === 'provider' ? $validated['provider'] : 'none',
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => [
                    'default_rate_bps' => (int) $validated['defaultRateBps'],
                    'zone_rates' => $zoneRates,
                    'fallback' => $validated['fallback'],
                ],
            ],
        );

        $this->dispatch('toast', type: 'success', message: 'Settings saved');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes', [
            'zones' => ShippingZone::query()->orderBy('id')->get(),
        ])->layout('admin.layouts.app')->title('Taxes');
    }

    /**
     * Convert empty-string inputs to null so integer validation passes.
     */
    private function normalizeInputs(): void
    {
        if ($this->defaultRateBps === '' || $this->defaultRateBps === null) {
            $this->defaultRateBps = null;
        } else {
            $this->defaultRateBps = (int) $this->defaultRateBps;
        }

        foreach ($this->zoneRates as $zoneId => $rate) {
            $this->zoneRates[$zoneId] = $rate === '' || $rate === null ? null : (int) $rate;
        }
    }
}
