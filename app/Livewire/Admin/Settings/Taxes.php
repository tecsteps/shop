<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSettings;
use Livewire\Component;

class Taxes extends Component
{
    public int $rate = 1900;

    public string $message = '';

    public function mount(): void
    {
        $this->rate = (int) (TaxSettings::first()?->default_rate_basis_points ?? 1900);
    }

    public function save(): void
    {
        $this->validate(['rate' => ['required', 'integer', 'min:0', 'max:10000']]);
        TaxSettings::updateOrCreate(['store_id' => app('current_store')->getKey()], ['mode' => 'exclusive', 'default_rate_basis_points' => $this->rate, 'rates_json' => ['DE' => $this->rate]]);
        $this->message = 'Tax settings saved';
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.taxes')->layout('layouts.admin');
    }
}
