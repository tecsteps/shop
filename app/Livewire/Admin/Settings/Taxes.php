<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\TaxSettings;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class Taxes extends AdminComponent
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public string $provider = 'none';

    public string $providerApiKey = '';

    /** @var list<array{zone_name: string, rate_percentage: string|float}> */
    public array $manualRates = [];

    public function mount(): void
    {
        $this->authorizeSettings();
        $settings = TaxSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->first();
        if ($settings) {
            $config = (array) $settings->config_json;
            $this->mode = (string) $this->enumValue($settings->mode);
            $this->provider = (string) $this->enumValue($settings->provider);
            $this->pricesIncludeTax = (bool) $settings->prices_include_tax;
            $this->manualRates = array_values((array) data_get($config, 'manual_rates', []));
            $this->providerApiKey = (string) data_get($config, 'api_key', '');
        }
        if ($this->manualRates === []) {
            $this->manualRates = [['zone_name' => '', 'rate_percentage' => '']];
        }
    }

    public function addManualRate(): void
    {
        $this->authorizeSettings();
        $this->manualRates[] = ['zone_name' => '', 'rate_percentage' => ''];
    }

    public function removeManualRate(int $index): void
    {
        $this->authorizeSettings();
        abort_unless(array_key_exists($index, $this->manualRates), 404);
        unset($this->manualRates[$index]);
        $this->manualRates = array_values($this->manualRates);
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $data = $this->validate([
            'mode' => ['required', Rule::in(['manual', 'provider'])],
            'pricesIncludeTax' => ['boolean'],
            'provider' => ['required', Rule::in(['none', 'stripe_tax'])],
            'providerApiKey' => ['nullable', 'string', 'max:500'],
            'manualRates' => ['array', 'max:100'],
            'manualRates.*.zone_name' => ['nullable', 'string', 'max:100'],
            'manualRates.*.rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data['manualRates'] as $index => $rate) {
            if (filled($rate['zone_name']) xor filled($rate['rate_percentage'])) {
                $field = filled($rate['zone_name']) ? "manualRates.{$index}.rate_percentage" : "manualRates.{$index}.zone_name";
                $this->addError($field, 'Complete both fields for this manual rate.');

                return;
            }
        }

        $rates = collect($data['manualRates'])->filter(fn (array $rate): bool => filled($rate['zone_name']) || filled($rate['rate_percentage']))->map(fn (array $rate): array => [
            'zone_name' => trim((string) $rate['zone_name']),
            'rate_percentage' => round((float) $rate['rate_percentage'], 4),
        ])->values()->all();

        $existing = TaxSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->first();
        $config = (array) ($existing?->config_json ?? []);
        $config['manual_rates'] = $rates;
        $config['api_key'] = $data['mode'] === 'provider' ? $data['providerApiKey'] : null;

        TaxSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $this->currentStore()->id], [
            'mode' => $data['mode'],
            'provider' => $data['mode'] === 'provider' ? $data['provider'] : 'none',
            'prices_include_tax' => (bool) $data['pricesIncludeTax'],
            'config_json' => $config,
        ]);
        $this->toast('Tax settings saved');
    }

    public function render(): View
    {
        return $this->admin(view('admin.settings.taxes'), 'Tax Settings', [['label' => 'Settings', 'url' => url('/admin/settings')], ['label' => 'Taxes']]);
    }

    private function authorizeSettings(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }
}
