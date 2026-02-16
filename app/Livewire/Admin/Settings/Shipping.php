<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Shipping'])]
class Shipping extends Component
{
    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public ?int $editingZoneId = null;

    public ?int $editingRateZoneId = null;

    public string $zoneName = '';

    public string $zoneCountries = '';

    public string $rateName = '';

    public string $rateAmount = '';

    public string $rateType = 'flat';

    public function openZoneModal(?int $zoneId = null): void
    {
        if ($zoneId) {
            $zone = ShippingZone::query()->find($zoneId);
            $this->editingZoneId = $zoneId;
            $this->zoneName = $zone->name;
            $this->zoneCountries = is_array($zone->countries_json) ? implode(', ', $zone->countries_json) : '';
        } else {
            $this->editingZoneId = null;
            $this->zoneName = '';
            $this->zoneCountries = '';
        }
        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        $this->validate(['zoneName' => ['required', 'string']]);
        $countries = array_map('trim', explode(',', $this->zoneCountries));

        if ($this->editingZoneId) {
            ShippingZone::query()->where('id', $this->editingZoneId)->update([
                'name' => $this->zoneName,
                'countries_json' => $countries,
            ]);
        } else {
            ShippingZone::query()->create([
                'store_id' => $store->id,
                'name' => $this->zoneName,
                'countries_json' => $countries,
                'is_active' => true,
            ]);
        }

        $this->showZoneModal = false;
        session()->flash('success', 'Shipping zone saved.');
    }

    public function deleteZone(int $zoneId): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        ShippingZone::query()->where('id', $zoneId)->where('store_id', $store->id)->delete();
        session()->flash('success', 'Zone deleted.');
    }

    public function openRateModal(int $zoneId): void
    {
        $this->editingRateZoneId = $zoneId;
        $this->rateName = '';
        $this->rateAmount = '';
        $this->rateType = 'flat';
        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        $this->validate([
            'rateName' => ['required', 'string'],
            'rateAmount' => ['required', 'numeric', 'min:0'],
        ]);

        ShippingRate::query()->create([
            'zone_id' => $this->editingRateZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'amount' => (int) (((float) $this->rateAmount) * 100),
            'is_active' => true,
        ]);

        $this->showRateModal = false;
        session()->flash('success', 'Rate added.');
    }

    public function deleteRate(int $rateId): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        ShippingRate::query()->where('id', $rateId)->delete();
        session()->flash('success', 'Rate deleted.');
    }

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.settings.shipping', [
            'zones' => ShippingZone::query()
                ->where('store_id', $store->id)
                ->with('rates')
                ->get(),
        ]);
    }
}
