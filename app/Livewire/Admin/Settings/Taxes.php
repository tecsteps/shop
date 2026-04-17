<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use App\Models\TaxSettings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public string $provider = 'none';

    public bool $pricesIncludeTax = false;

    public function mount(): void
    {
        $store = app('current_store');
        $this->authorize('viewSettings', $store);

        $settings = TaxSettings::query()->find($store->getKey());

        if ($settings !== null) {
            $this->mode = $settings->mode->value;
            $this->provider = $settings->provider->value;
            $this->pricesIncludeTax = (bool) $settings->prices_include_tax;
        }
    }

    public function save(): void
    {
        $store = app('current_store');
        $this->authorize('updateSettings', $store);

        $this->validate([
            'mode' => ['required', 'in:'.implode(',', TaxMode::values())],
            'provider' => ['required', 'in:'.implode(',', TaxProviderType::values())],
        ]);

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->getKey()],
            [
                'mode' => TaxMode::from($this->mode)->value,
                'provider' => TaxProviderType::from($this->provider)->value,
                'prices_include_tax' => $this->pricesIncludeTax ? 1 : 0,
                'config_json' => [],
            ],
        );

        session()->flash('status', 'Tax settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes', [
            'modes' => TaxMode::values(),
            'providers' => TaxProviderType::values(),
        ]);
    }
}
