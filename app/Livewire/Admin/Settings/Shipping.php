<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Shipping extends Component
{
    public string $zoneName = '';

    /** @var array<int, string> */
    public array $zoneCountries = [];

    public ?int $editingZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    /** @var array<string, mixed> */
    public array $rateConfig = [];

    public bool $rateActive = true;

    public ?int $editingRateId = null;

    public ?int $rateZoneId = null;

    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public string $testCountry = '';

    public string $testState = '';

    public string $testCity = '';

    public string $testZip = '';

    /** @var array<string, mixed>|null */
    public ?array $testResult = null;

    public function openZoneModal(?int $zoneId = null): void
    {
        if ($zoneId) {
            $zone = ShippingZone::findOrFail($zoneId);
            $this->editingZoneId = $zone->id;
            $this->zoneName = $zone->name;
            $this->zoneCountries = $zone->countries_json ?? [];
        } else {
            $this->editingZoneId = null;
            $this->zoneName = '';
            $this->zoneCountries = [];
        }
        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
        ]);

        if ($this->editingZoneId) {
            $zone = ShippingZone::findOrFail($this->editingZoneId);
            $zone->update([
                'name' => $this->zoneName,
                'countries_json' => $this->zoneCountries,
            ]);
        } else {
            ShippingZone::create([
                'store_id' => app('current_store')->id,
                'name' => $this->zoneName,
                'countries_json' => $this->zoneCountries,
            ]);
        }

        $this->reset('zoneName', 'zoneCountries', 'editingZoneId', 'showZoneModal');
        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved.');
    }

    public function deleteZone(int $zoneId): void
    {
        $zone = ShippingZone::findOrFail($zoneId);
        $zone->rates()->delete();
        $zone->delete();
        $this->dispatch('toast', type: 'success', message: 'Shipping zone deleted.');
    }

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        $this->rateZoneId = $zoneId;

        if ($rateId) {
            $rate = ShippingRate::findOrFail($rateId);
            $this->editingRateId = $rate->id;
            $this->rateName = $rate->name;
            $this->rateType = $rate->type->value;
            $this->rateConfig = $rate->config_json ?? [];
            $this->rateActive = $rate->is_active;
        } else {
            $this->editingRateId = null;
            $this->rateName = '';
            $this->rateType = 'flat';
            $this->rateConfig = [];
            $this->rateActive = true;
        }
        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', 'string', 'in:flat,weight,price,carrier'],
        ]);

        $data = [
            'name' => $this->rateName,
            'type' => ShippingRateType::from($this->rateType),
            'config_json' => $this->rateConfig,
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId) {
            $rate = ShippingRate::findOrFail($this->editingRateId);
            $rate->update($data);
        } else {
            ShippingRate::create(array_merge($data, ['zone_id' => $this->rateZoneId]));
        }

        $this->reset('rateName', 'rateType', 'rateConfig', 'rateActive', 'editingRateId', 'rateZoneId', 'showRateModal');
        $this->dispatch('toast', type: 'success', message: 'Shipping rate saved.');
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::findOrFail($rateId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Shipping rate deleted.');
    }

    public function testShippingAddress(): void
    {
        $zones = ShippingZone::with('rates')
            ->where('store_id', app('current_store')->id)
            ->get();

        $matchedZone = null;
        foreach ($zones as $zone) {
            $countries = $zone->countries_json ?? [];
            if (in_array($this->testCountry, $countries)) {
                $matchedZone = $zone;
                break;
            }
        }

        if ($matchedZone) {
            $rates = $matchedZone->rates->where('is_active', true)->map(function (ShippingRate $rate) {
                return [
                    'name' => $rate->name,
                    'type' => $rate->type->value,
                    'price' => $rate->config_json['price'] ?? 0,
                ];
            })->values()->all();

            $this->testResult = [
                'matched' => true,
                'zone_name' => $matchedZone->name,
                'rates' => $rates,
            ];
        } else {
            $this->testResult = ['matched' => false];
        }
    }

    /**
     * @return Collection<int, ShippingZone>
     */
    public function getZones(): Collection
    {
        return ShippingZone::with('rates')
            ->where('store_id', app('current_store')->id)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.settings.shipping', [
            'zones' => $this->getZones(),
        ])->layout('layouts.admin', ['title' => 'Shipping Settings']);
    }
}
