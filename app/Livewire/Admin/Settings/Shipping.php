<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Shipping extends Component
{
    public bool $showZoneForm = false;

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    public string $countries = '';

    public bool $showRateForm = false;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public int $rateAmount = 0;

    public function openZoneForm(?int $zoneId = null): void
    {
        $this->resetZoneForm();

        if ($zoneId) {
            $zone = $this->findZone($zoneId);
            if ($zone) {
                $this->editingZoneId = $zone->id;
                $this->zoneName = $zone->name;
                $this->countries = is_array($zone->countries_json) ? implode(', ', $zone->countries_json) : '';
            }
        }

        $this->showZoneForm = true;
    }

    public function saveZone(): void
    {
        $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'countries' => ['required', 'string'],
        ]);

        $countriesArray = array_map('trim', explode(',', $this->countries));

        if ($this->editingZoneId) {
            $zone = $this->findZone($this->editingZoneId);
            if ($zone) {
                $zone->update([
                    'name' => $this->zoneName,
                    'countries_json' => $countriesArray,
                ]);
            }
        } else {
            ShippingZone::create([
                'name' => $this->zoneName,
                'countries_json' => $countriesArray,
                'regions_json' => [],
            ]);
        }

        $this->resetZoneForm();
        $this->showZoneForm = false;
    }

    public function deleteZone(int $zoneId): void
    {
        $zone = $this->findZone($zoneId);
        if ($zone) {
            $zone->rates()->delete();
            $zone->delete();
        }
    }

    public function openRateForm(int $zoneId): void
    {
        $this->rateZoneId = $zoneId;
        $this->rateName = '';
        $this->rateType = 'flat';
        $this->rateAmount = 0;
        $this->showRateForm = true;
    }

    public function saveRate(): void
    {
        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateAmount' => ['required', 'integer', 'min:0'],
        ]);

        ShippingRate::create([
            'zone_id' => $this->rateZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => ['amount' => $this->rateAmount],
            'is_active' => true,
        ]);

        $this->showRateForm = false;
    }

    public function render(): View
    {
        $zones = ShippingZone::query()->with('rates')->get();

        return view('livewire.admin.settings.shipping', [
            'zones' => $zones,
        ]);
    }

    private function resetZoneForm(): void
    {
        $this->editingZoneId = null;
        $this->zoneName = '';
        $this->countries = '';
        $this->resetValidation();
    }

    private function findZone(int $zoneId): ?ShippingZone
    {
        return ShippingZone::query()->find($zoneId);
    }
}
