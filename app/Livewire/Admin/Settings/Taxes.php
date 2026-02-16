<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Taxes'])]
class Taxes extends Component
{
    public string $mode = 'manual';

    public string $rate_basis_points = '0';

    public string $tax_name = 'Tax';

    public bool $prices_include_tax = false;

    public bool $charge_tax_on_shipping = false;

    public function mount(): void
    {
        $store = app('current_store');
        $settings = $store->taxSettings;

        if ($settings) {
            $this->mode = $settings->mode->value;
            $this->rate_basis_points = (string) $settings->rate_basis_points;
            $this->tax_name = $settings->tax_name ?? 'Tax';
            $this->prices_include_tax = $settings->prices_include_tax;
            $this->charge_tax_on_shipping = $settings->charge_tax_on_shipping;
        }
    }

    public function save(): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->mode,
                'rate_basis_points' => (int) $this->rate_basis_points,
                'tax_name' => $this->tax_name,
                'prices_include_tax' => $this->prices_include_tax,
                'charge_tax_on_shipping' => $this->charge_tax_on_shipping,
            ]
        );

        session()->flash('success', 'Tax settings saved.');
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.taxes');
    }
}
