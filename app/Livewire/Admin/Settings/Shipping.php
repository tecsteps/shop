<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Shipping extends Component
{
    public string $zoneName = '';

    public string $zoneCountries = 'DE,AT,CH';

    public ?int $zoneId = null;

    public string $rateName = '';

    public int $rateAmount = 499;

    public function addZone(): void
    {
        if ($this->zoneName === '') {
            return;
        }

        ShippingZone::create([
            'store_id' => app('current_store')->id,
            'name' => $this->zoneName,
            'countries_json' => array_map('trim', explode(',', $this->zoneCountries)),
            'regions_json' => [],
        ]);

        $this->zoneName = '';
        $this->zoneCountries = 'DE,AT,CH';
    }

    public function addRate(int $zoneId): void
    {
        if ($this->rateName === '') {
            return;
        }

        ShippingRate::create([
            'zone_id' => $zoneId,
            'name' => $this->rateName,
            'type' => 'flat',
            'config_json' => ['amount' => $this->rateAmount],
            'is_active' => true,
        ]);

        $this->rateName = '';
        $this->rateAmount = 499;
    }

    public function deleteRate(int $rateId): void
    {
        ShippingRate::where('id', $rateId)->delete();
    }

    public function render()
    {
        $zones = ShippingZone::query()->with('rates')->get();

        return view('livewire.admin.settings.shipping', compact('zones'))->title('Shipping');
    }
}
