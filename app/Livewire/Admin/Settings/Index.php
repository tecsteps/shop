<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $tab = 'general';

    public string $storeName = '';

    public string $defaultCurrency = 'USD';

    public string $timezone = 'UTC';

    /** @var array<ShippingZone> */
    public array $shippingZones = [];

    public string $taxMode = 'manual';

    public bool $pricesIncludeTax = false;

    public function mount(): void
    {
        $this->authorize('manageSettings', Store::class);

        $store = app('current_store');
        $this->storeName = $store->name;
        $this->defaultCurrency = $store->default_currency;
        $this->timezone = $store->timezone;

        $taxSettings = TaxSettings::where('store_id', $store->id)->first();
        if ($taxSettings) {
            $this->taxMode = $taxSettings->mode->value;
            $this->pricesIncludeTax = $taxSettings->prices_include_tax;
        }
    }

    public function saveGeneral(): void
    {
        $this->authorize('manageSettings', Store::class);

        $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');
        $store->update([
            'name' => $this->storeName,
            'default_currency' => $this->defaultCurrency,
            'timezone' => $this->timezone,
        ]);

        session()->flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);
    }

    public function saveTax(): void
    {
        $this->authorize('manageShippingTax', Store::class);

        $store = app('current_store');

        TaxSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->taxMode,
                'provider' => 'manual',
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => [],
            ]
        );

        session()->flash('toast', ['type' => 'success', 'message' => __('Tax settings saved.')]);
    }

    public function render(): View
    {
        $store = app('current_store');

        return view('livewire.admin.settings.index', [
            'shippingZones' => ShippingZone::where('store_id', $store->id)->with('rates')->get(),
        ]);
    }
}
