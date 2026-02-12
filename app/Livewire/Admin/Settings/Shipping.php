<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Shipping Settings')]
class Shipping extends Component
{
    public ?int $editingZoneId = null;

    public string $zoneName = '';

    /** @var array<string> */
    public array $zoneCountries = [];

    public ?int $editingRateId = null;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public string $rateAmount = '';

    public bool $rateActive = true;

    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public function openZoneModal(?int $zoneId = null): void
    {
        if ($zoneId) {
            $zone = ShippingZone::findOrFail($zoneId);
            $this->editingZoneId = $zone->id;
            $this->zoneName = $zone->name;
            $this->zoneCountries = $zone->countries ?? [];
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

        $store = app('current_store');

        if ($this->editingZoneId) {
            ShippingZone::findOrFail($this->editingZoneId)->update([
                'name' => $this->zoneName,
                'countries' => $this->zoneCountries,
            ]);
        } else {
            ShippingZone::create([
                'store_id' => $store->id,
                'name' => $this->zoneName,
                'countries' => $this->zoneCountries,
            ]);
        }

        $this->showZoneModal = false;
        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved.');
    }

    public function deleteZone(int $zoneId): void
    {
        ShippingZone::findOrFail($zoneId)->delete();
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
            $config = $rate->config_json ?? [];
            $this->rateAmount = isset($config['amount'])
                ? number_format($config['amount'] / 100, 2, '.', '')
                : '';
            $this->rateActive = $rate->is_active;
        } else {
            $this->editingRateId = null;
            $this->rateName = '';
            $this->rateType = 'flat';
            $this->rateAmount = '';
            $this->rateActive = true;
        }
        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', 'in:flat,weight,price,carrier'],
        ]);

        $configJson = [];
        if ($this->rateAmount !== '') {
            $configJson['amount'] = (int) round((float) $this->rateAmount * 100);
        }

        $data = [
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => $configJson,
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId) {
            ShippingRate::findOrFail($this->editingRateId)->update($data);
        } else {
            ShippingRate::create(array_merge($data, [
                'shipping_zone_id' => $this->rateZoneId,
            ]));
        }

        $this->showRateModal = false;
        $this->dispatch('toast', type: 'success', message: 'Shipping rate saved.');
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::findOrFail($rateId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Shipping rate deleted.');
    }

    public function render(): View
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
