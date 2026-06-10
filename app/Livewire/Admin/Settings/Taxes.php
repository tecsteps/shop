<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tax settings page (spec 03 section 11.4): manual vs provider mode, the
 * manual default rate (stored in basis points), tax-inclusive pricing, and
 * whether shipping is taxable.
 */
#[Layout('layouts::admin')]
class Taxes extends Component
{
    use AuthorizesRequests, SendsToasts;

    public string $mode = 'manual';

    public string $manualRate = '19.00';

    public string $taxName = 'Tax';

    public bool $pricesIncludeTax = false;

    public bool $shippingTaxable = true;

    public string $provider = 'none';

    public string $providerApiKey = '';

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('viewSettings', $store);

        $settings = TaxSettings::query()->find($store->getKey());

        if ($settings !== null) {
            $this->mode = $settings->mode->value;
            $this->manualRate = number_format($settings->defaultRateBasisPoints() / 100, 2, '.', '');
            $this->taxName = $settings->taxName();
            $this->pricesIncludeTax = $settings->prices_include_tax;
            $this->shippingTaxable = $settings->shippingTaxable();
            $this->provider = $settings->provider ?? 'none';
            $this->providerApiKey = (string) ($settings->config_json['provider_api_key'] ?? '');
        }
    }

    public function save(): void
    {
        $store = $this->store();

        $this->authorize('updateSettings', $store);

        $this->validate([
            'mode' => ['required', 'in:manual,provider'],
            'manualRate' => ['required_if:mode,manual', 'nullable', 'numeric', 'min:0', 'max:100'],
            'taxName' => ['nullable', 'string', 'max:255'],
            'provider' => ['required_if:mode,provider', 'nullable', 'string', 'max:255'],
            'providerApiKey' => ['nullable', 'string', 'max:255'],
        ]);

        $config = [
            'default_rate_bps' => (int) round((float) str_replace(',', '.', $this->manualRate) * 100),
            'shipping_taxable' => $this->shippingTaxable,
            'tax_name' => trim($this->taxName) !== '' ? trim($this->taxName) : 'Tax',
        ];

        if ($this->mode === 'provider' && trim($this->providerApiKey) !== '') {
            $config['provider_api_key'] = trim($this->providerApiKey);
        }

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->getKey()],
            [
                'mode' => $this->mode,
                'provider' => $this->mode === 'provider' ? $this->provider : 'none',
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => $config,
            ],
        );

        $this->toast(__('Settings saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes')->title(__('Taxes'));
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
