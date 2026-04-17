<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Shipping extends Component
{
    public string $newZoneName = '';

    /** @var array<int, string> */
    public array $newZoneCountries = [];

    public function mount(): void
    {
        $store = app('current_store');
        $this->authorize('viewSettings', $store);
    }

    public function addZone(): void
    {
        $store = app('current_store');
        $this->authorize('updateSettings', $store);

        $this->validate([
            'newZoneName' => ['required', 'string', 'max:255'],
        ]);

        $zone = new ShippingZone([
            'name' => $this->newZoneName,
            'countries_json' => $this->newZoneCountries,
            'regions_json' => [],
        ]);
        $zone->store_id = (int) $store->getKey();
        $zone->save();

        $this->reset(['newZoneName', 'newZoneCountries']);
        session()->flash('status', 'Zone added.');
    }

    public function deleteZone(int $zoneId): void
    {
        $store = app('current_store');
        $this->authorize('updateSettings', $store);

        ShippingZone::query()->where('id', $zoneId)->where('store_id', $store->getKey())->delete();
        session()->flash('status', 'Zone removed.');
    }

    public function deleteRate(int $rateId): void
    {
        $store = app('current_store');
        $this->authorize('updateSettings', $store);

        ShippingRate::query()->where('id', $rateId)->delete();
        session()->flash('status', 'Rate removed.');
    }

    public function render(): View
    {
        $zones = ShippingZone::query()->with('rates')->get();

        return view('livewire.admin.settings.shipping', ['zones' => $zones]);
    }
}
