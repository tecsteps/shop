<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Shipping Settings')]
class Shipping extends Component
{
    public string $zoneName = '';

    public string $zoneCountries = '';

    public ?int $editingZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public string $rateAmount = '';

    public ?int $editingRateZoneId = null;

    public function mount(): void {}

    public function openZoneModal(?int $zoneId = null): void
    {
        $this->editingZoneId = $zoneId;
        if ($zoneId) {
            $zone = ShippingZone::findOrFail($zoneId);
            $this->zoneName = $zone->name;
            $this->zoneCountries = implode(', ', $zone->countries_json ?? []);
        } else {
            $this->zoneName = '';
            $this->zoneCountries = '';
        }
        $this->modal('zone-form')->show();
    }

    public function saveZone(): void
    {
        $store = app('current_store');
        $countries = array_map('trim', explode(',', $this->zoneCountries));

        $data = [
            'store_id' => $store->id,
            'name' => $this->zoneName,
            'countries_json' => $countries,
            'is_active' => true,
        ];

        if ($this->editingZoneId) {
            ShippingZone::where('id', $this->editingZoneId)->update($data);
        } else {
            ShippingZone::create($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved.');
        $this->modal('zone-form')->close();
    }

    public function deleteZone(int $zoneId): void
    {
        ShippingZone::where('id', $zoneId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Zone deleted.');
    }

    public function openRateModal(int $zoneId): void
    {
        $this->editingRateZoneId = $zoneId;
        $this->rateName = '';
        $this->rateType = 'flat';
        $this->rateAmount = '';
        $this->modal('rate-form')->show();
    }

    public function saveRate(): void
    {
        ShippingRate::create([
            'zone_id' => $this->editingRateZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => ['amount' => (int) round((float) $this->rateAmount * 100)],
            'is_active' => true,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Rate added.');
        $this->modal('rate-form')->close();
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::where('id', $rateId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Rate deleted.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $store = app('current_store');
        $zones = ShippingZone::where('store_id', $store->id)->with('rates')->get();

        return view('livewire.admin.settings.shipping', ['zones' => $zones]);
    }
}
