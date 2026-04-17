<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Taxes extends Component
{
    #[Validate('required|string|in:manual,provider')]
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    #[Validate('nullable|string|max:255')]
    public string $taxName = '';

    #[Validate('nullable|integer|min:0|max:10000')]
    public int $rateBasisPoints = 0;

    public function mount(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $settings = TaxSettings::query()->firstOrNew(['store_id' => $store->id]);

        $this->mode = $settings->mode?->value ?? 'manual';
        $this->pricesIncludeTax = (bool) $settings->prices_include_tax;
        $config = (array) ($settings->config_json ?? []);
        $this->taxName = (string) ($config['name'] ?? '');
        $this->rateBasisPoints = (int) ($config['rate_basis_points'] ?? 0);
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->mode,
                'prices_include_tax' => $this->pricesIncludeTax,
                'config_json' => [
                    'name' => $this->taxName,
                    'rate_basis_points' => $this->rateBasisPoints,
                ],
            ]
        );

        session()->flash('status', 'Tax settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes');
    }
}
