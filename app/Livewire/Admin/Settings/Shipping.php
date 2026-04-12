<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Shipping extends Component
{
    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public ?int $activeZoneId = null;

    #[Validate('required|string|max:255')]
    public string $zoneName = '';

    #[Validate('nullable|string')]
    public string $zoneCountries = '';

    #[Validate('required|string|max:255')]
    public string $rateName = '';

    #[Validate('required|string|in:flat,weight,price,carrier')]
    public string $rateType = 'flat';

    #[Validate('nullable|integer|min:0')]
    public int $rateAmount = 0;

    public function openZoneModal(): void
    {
        $this->reset('zoneName', 'zoneCountries');
        $this->showZoneModal = true;
    }

    public function createZone(): void
    {
        $this->validate([
            'zoneName' => 'required|string|max:255',
            'zoneCountries' => 'nullable|string',
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $countries = array_values(array_filter(array_map(
            fn (string $code): string => strtoupper(trim($code)),
            explode(',', $this->zoneCountries)
        )));

        ShippingZone::create([
            'store_id' => $store->id,
            'name' => $this->zoneName,
            'countries_json' => $countries,
            'regions_json' => [],
        ]);

        $this->showZoneModal = false;
        session()->flash('status', 'Zone created.');
    }

    public function deleteZone(int $zoneId): void
    {
        $zone = ShippingZone::query()->findOrFail($zoneId);
        $zone->delete();
        session()->flash('status', 'Zone deleted.');
    }

    public function openRateModal(int $zoneId): void
    {
        $this->activeZoneId = $zoneId;
        $this->reset('rateName', 'rateType', 'rateAmount');
        $this->rateType = 'flat';
        $this->showRateModal = true;
    }

    public function createRate(): void
    {
        $this->validate([
            'rateName' => 'required|string|max:255',
            'rateType' => 'required|string|in:flat,weight,price,carrier',
            'rateAmount' => 'nullable|integer|min:0',
        ]);

        if ($this->activeZoneId === null) {
            return;
        }

        ShippingRate::create([
            'zone_id' => $this->activeZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => ['amount' => $this->rateAmount],
            'is_active' => true,
        ]);

        $this->showRateModal = false;
        $this->activeZoneId = null;
        session()->flash('status', 'Rate added.');
    }

    public function deleteRate(int $rateId): void
    {
        $rate = ShippingRate::query()->findOrFail($rateId);
        $rate->delete();
        session()->flash('status', 'Rate deleted.');
    }

    public function render(): View
    {
        $zones = ShippingZone::query()
            ->with('rates')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.settings.shipping', [
            'zones' => $zones,
        ]);
    }
}
