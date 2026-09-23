<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingZone;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Shipping extends Component
{
    public string $zoneName = '';

    public string $countries = 'DE';

    public ?int $zoneId = null;

    public string $rateName = 'Standard Shipping';

    public int $rateAmount = 499;

    public ?int $minOrderAmount = null;

    public ?int $maxOrderAmount = null;

    public function saveZone(): void
    {
        Gate::authorize('manage-shipping');
        $this->validate(['zoneName' => ['required', 'string', 'max:255'], 'countries' => ['required', 'string', 'max:500']]);
        $countryCodes = array_values(array_filter(array_unique(array_map(static fn (string $code): string => strtoupper(trim($code)), explode(',', $this->countries)))));

        foreach ($countryCodes as $countryCode) {
            validator(['country' => $countryCode], ['country' => ['required', 'size:2']])->validate();
        }

        $zone = ShippingZone::create(['store_id' => app('current_store')->id, 'name' => $this->zoneName, 'countries' => $countryCodes]);
        $this->zoneId = $zone->id;
        session()->flash('status', 'Shipping zone saved.');
    }

    public function saveRate(): void
    {
        Gate::authorize('manage-shipping');
        $this->validate([
            'zoneId' => ['required', 'integer'],
            'rateName' => ['required', 'string', 'max:255'],
            'rateAmount' => ['required', 'integer', 'min:0'],
            'minOrderAmount' => ['nullable', 'integer', 'min:0'],
            'maxOrderAmount' => ['nullable', 'integer', 'gte:minOrderAmount'],
        ]);
        $zone = ShippingZone::query()->findOrFail($this->zoneId);
        $zone->rates()->create(['name' => $this->rateName, 'price_amount' => $this->rateAmount, 'min_order_amount' => $this->minOrderAmount, 'max_order_amount' => $this->maxOrderAmount]);
        session()->flash('status', 'Shipping rate saved.');
    }

    public function deleteZone(int $zoneId): void
    {
        Gate::authorize('manage-shipping');
        ShippingZone::query()->findOrFail($zoneId)->delete();
    }

    public function render(): mixed
    {
        Gate::authorize('manage-shipping');

        return view('livewire.admin.settings.shipping', ['zones' => ShippingZone::query()->with('rates')->orderBy('name')->get()])->layout('layouts.admin');
    }
}
