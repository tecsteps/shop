<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Shipping extends Component
{
    public string $newZoneName = '';

    public string $newZoneCountries = '';

    /** @var array<int, array<string, mixed>> */
    public array $newRate = [];

    public function addZone(): void
    {
        $this->validate([
            'newZoneName' => 'required|string|max:255',
        ]);

        $store = app('current_store');
        ShippingZone::create([
            'store_id' => $store->id,
            'name' => $this->newZoneName,
            'countries_json' => array_values(array_filter(array_map('trim', explode(',', $this->newZoneCountries)))),
            'regions_json' => [],
        ]);

        $this->newZoneName = '';
        $this->newZoneCountries = '';
    }

    public function removeZone(int $id): void
    {
        ShippingZone::query()->whereKey($id)->delete();
    }

    public function addRate(int $zoneId): void
    {
        $entry = $this->newRate[$zoneId] ?? [];
        $name = trim((string) ($entry['name'] ?? ''));
        $type = $entry['type'] ?? 'flat';
        $amount = (int) ($entry['amount'] ?? 0);

        if ($name === '') {
            return;
        }

        ShippingRate::create([
            'zone_id' => $zoneId,
            'name' => $name,
            'type' => $type,
            'config_json' => ['amount' => $amount],
            'is_active' => true,
        ]);

        $this->newRate[$zoneId] = ['name' => '', 'type' => 'flat', 'amount' => 0];
    }

    public function removeRate(int $rateId): void
    {
        ShippingRate::query()->whereKey($rateId)->delete();
    }

    public function render()
    {
        $store = app('current_store');
        $zones = ShippingZone::query()->where('store_id', $store->id)->with('rates')->get();

        return view('livewire.admin.settings.shipping', [
            'zones' => $zones,
            'rateTypes' => ShippingRateType::cases(),
        ]);
    }
}
