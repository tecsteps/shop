<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Component;

class Shipping extends Component
{
    public string $zoneName = 'Domestic';

    public string $rateName = 'Standard Shipping';

    public int $amount = 499;

    public string $message = '';

    public function save(): void
    {
        $data = $this->validate(['zoneName' => ['required', 'string'], 'rateName' => ['required', 'string'], 'amount' => ['required', 'integer', 'min:0']]);
        $zone = ShippingZone::updateOrCreate(['store_id' => app('current_store')->getKey(), 'name' => $data['zoneName']], ['countries_json' => ['DE'], 'regions_json' => []]);
        ShippingRate::updateOrCreate(['shipping_zone_id' => $zone->getKey(), 'name' => $data['rateName']], ['type' => 'flat', 'price_amount' => $data['amount'], 'currency' => app('current_store')->default_currency, 'is_active' => true]);
        $this->message = 'Shipping rate saved';
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.shipping', ['zones' => ShippingZone::with('rates')->latest()->get()])->layout('layouts.admin');
    }
}
