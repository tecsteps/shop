<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Shipping extends Component
{
    public ?ShippingZone $editingZone = null;

    public string $zoneName = '';

    /** @var array<string> */
    public array $zoneCountries = [];

    public ?ShippingRate $editingRate = null;

    public int $editingZoneId = 0;

    public string $rateName = '';

    public string $rateType = 'flat';

    /** @var array<string, mixed> */
    public array $rateConfig = ['price' => '0'];

    public bool $rateActive = true;

    /** @var array{country: string, state: string, city: string, zip: string} */
    public array $testAddress = ['country' => '', 'state' => '', 'city' => '', 'zip' => ''];

    /** @var ?array<string, mixed> */
    public ?array $testResult = null;

    public function getZonesProperty()
    {
        return ShippingZone::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->with('rates')
            ->get();
    }

    public function openZoneModal(?ShippingZone $zone = null): void
    {
        $this->editingZone = $zone;
        $this->zoneName = $zone?->name ?? '';
        $this->zoneCountries = $zone?->countries ?? [];
        $this->modal('zone-form')->show();
    }

    public function saveZone(): void
    {
        $this->validate([
            'zoneName' => 'required|string|max:255',
        ]);

        $data = [
            'store_id' => session('store_id'),
            'name' => $this->zoneName,
            'countries' => $this->zoneCountries,
        ];

        if ($this->editingZone) {
            $this->editingZone->update($data);
        } else {
            ShippingZone::withoutGlobalScopes()->create($data);
        }

        $this->modal('zone-form')->close();
        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved.');
    }

    public function deleteZone(int $zoneId): void
    {
        ShippingZone::withoutGlobalScopes()->findOrFail($zoneId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Shipping zone deleted.');
    }

    public function openRateModal(int $zoneId, ?ShippingRate $rate = null): void
    {
        $this->editingZoneId = $zoneId;
        $this->editingRate = $rate;
        $this->rateName = $rate?->name ?? '';
        $this->rateType = $rate?->type ?? 'flat';
        $this->rateConfig = $rate?->config ?? ['price' => '0'];
        $this->rateActive = $rate?->is_active ?? true;
        $this->modal('rate-form')->show();
    }

    public function saveRate(): void
    {
        $this->validate([
            'rateName' => 'required|string|max:255',
        ]);

        $data = [
            'shipping_zone_id' => $this->editingZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config' => $this->rateConfig,
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRate) {
            $this->editingRate->update($data);
        } else {
            ShippingRate::create($data);
        }

        $this->modal('rate-form')->close();
        $this->dispatch('toast', type: 'success', message: 'Shipping rate saved.');
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::findOrFail($rateId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Shipping rate deleted.');
    }

    public function testShippingAddress(): void
    {
        if (app()->bound(ShippingService::class)) {
            $service = app(ShippingService::class);
            $zones = $this->zones;
            $matched = $zones->first(function ($zone) {
                return in_array($this->testAddress['country'], $zone->countries ?? []);
            });

            if ($matched) {
                $this->testResult = [
                    'zone' => $matched->name,
                    'rates' => $matched->rates->where('is_active', true)->map(fn ($r) => [
                        'name' => $r->name,
                        'price' => $r->config['price'] ?? 0,
                    ])->values()->toArray(),
                ];
            } else {
                $this->testResult = ['zone' => null, 'rates' => []];
            }
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.shipping');
    }
}
