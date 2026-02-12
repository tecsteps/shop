<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Tax Settings')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = false;

    public int $taxRateBasisPoints = 0;

    public string $taxName = '';

    public bool $shippingTaxable = false;

    public function mount(): void
    {
        $store = app('current_store');
        $tax = TaxSettings::where('store_id', $store->id)->first();

        if ($tax) {
            $this->mode = $tax->mode->value;
            $this->pricesIncludeTax = $tax->prices_include_tax;
            $this->taxRateBasisPoints = $tax->tax_rate_basis_points;
            $this->taxName = $tax->tax_name ?? '';
            $this->shippingTaxable = $tax->shipping_taxable;
        }
    }

    public function save(): void
    {
        $this->validate([
            'mode' => ['required', 'in:manual,provider'],
            'taxRateBasisPoints' => ['required', 'integer', 'min:0', 'max:10000'],
            'taxName' => ['nullable', 'string', 'max:50'],
        ]);

        $store = app('current_store');

        TaxSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->mode,
                'prices_include_tax' => $this->pricesIncludeTax,
                'tax_rate_basis_points' => $this->taxRateBasisPoints,
                'tax_name' => $this->taxName,
                'shipping_taxable' => $this->shippingTaxable,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Tax settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes', [
            'modes' => TaxMode::cases(),
        ]);
    }
}
