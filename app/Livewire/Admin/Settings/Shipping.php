<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Shipping extends Component
{
    public string $newZoneName = '';

    public string $newZoneCountries = '';

    public string $newRateName = '';

    public int $newRatePrice = 0;

    public ?int $selectedZoneId = null;

    public function createZone(): void
    {
        $this->validate([
            'newZoneName' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');
        $countries = $this->newZoneCountries
            ? array_map('trim', explode(',', $this->newZoneCountries))
            : [];

        ShippingZone::create([
            'store_id' => $store->id,
            'name' => $this->newZoneName,
            'countries_json' => $countries,
        ]);

        $this->newZoneName = '';
        $this->newZoneCountries = '';
        $this->dispatch('toast', type: 'success', message: __('Shipping zone created.'));
    }

    public function deleteZone(int $zoneId): void
    {
        ShippingZone::where('store_id', app('current_store')->id)->findOrFail($zoneId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Shipping zone deleted.'));
    }

    public function addRate(): void
    {
        if (! $this->selectedZoneId) {
            return;
        }

        $this->validate([
            'newRateName' => ['required', 'string', 'max:255'],
            'newRatePrice' => ['required', 'integer', 'min:0'],
        ]);

        ShippingRate::create([
            'zone_id' => $this->selectedZoneId,
            'name' => $this->newRateName,
            'type' => 'flat',
            'config_json' => ['price' => $this->newRatePrice],
            'is_active' => true,
        ]);

        $this->newRateName = '';
        $this->newRatePrice = 0;
        $this->dispatch('toast', type: 'success', message: __('Shipping rate added.'));
    }

    public function deleteRate(int $rateId): void
    {
        $storeId = app('current_store')->id;
        $rate = ShippingRate::whereHas('zone', fn ($q) => $q->where('store_id', $storeId))
            ->findOrFail($rateId);
        $rate->delete();
        $this->dispatch('toast', type: 'success', message: __('Shipping rate deleted.'));
    }

    #[Computed]
    public function zones(): \Illuminate\Database\Eloquent\Collection
    {
        return ShippingZone::where('store_id', app('current_store')->id)
            ->with('rates')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.shipping');
    }
}
