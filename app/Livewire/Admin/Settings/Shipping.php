<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\View\View;
use Livewire\Component;

class Shipping extends Component
{
    use UsesAdminStore;

    public string $zoneName = '';

    public string $countries = 'DE';

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public int $rateAmount = 0;

    public function createZone(): void
    {
        $validated = $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'countries' => ['required', 'string', 'max:500'],
        ]);

        ShippingZone::query()->create([
            'store_id' => $this->currentStore()->id,
            'name' => $validated['zoneName'],
            'countries_json' => collect(explode(',', $validated['countries']))->map(fn (string $country): string => strtoupper(trim($country)))->filter()->values()->all(),
            'regions_json' => [],
        ]);

        $this->reset('zoneName', 'countries');
        $this->countries = 'DE';
        $this->notify('Shipping zone created.');
    }

    public function createRate(): void
    {
        $validated = $this->validate([
            'rateZoneId' => ['required', 'integer', 'exists:shipping_zones,id'],
            'rateName' => ['required', 'string', 'max:255'],
            'rateAmount' => ['required', 'integer', 'min:0'],
        ]);

        $zone = ShippingZone::query()->whereKey($validated['rateZoneId'])->firstOrFail();

        $zone->rates()->create([
            'name' => $validated['rateName'],
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => $validated['rateAmount']],
            'is_active' => true,
        ]);

        $this->reset('rateZoneId', 'rateName', 'rateAmount');
        $this->notify('Shipping rate created.');
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::query()->whereKey($rateId)->firstOrFail()->delete();
        $this->notify('Shipping rate deleted.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.shipping', [
            'zones' => ShippingZone::query()->with('rates')->orderBy('name')->get(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Shipping',
        ]);
    }
}
