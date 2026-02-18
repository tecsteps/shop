<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Taxes extends Component
{
    public string $taxMode = 'manual';

    public int $taxRate = 1900;

    public bool $pricesIncludeTax = false;

    public function mount(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $settings = TaxSettings::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->first();

        if ($settings) {
            $this->taxMode = $settings->mode->value;
            $this->taxRate = $settings->rate_percent ?? 1900;
            $this->pricesIncludeTax = $settings->prices_include_tax ?? false;
        }
    }

    public function save(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        TaxSettings::query()->withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => $this->taxMode,
                'rate_percent' => $this->taxRate,
                'prices_include_tax' => $this->pricesIncludeTax,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Tax settings saved successfully.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.taxes');
    }
}
