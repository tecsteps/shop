<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Shipping extends AdminComponent
{
    public string $zoneName = '';

    public string $countries = '';

    public ?int $activeZoneId = null;

    public string $rateName = '';

    public string $rateAmount = '0';

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->currentStore());
    }

    public function createZone(): void
    {
        $this->authorizeWrite();
        $validated = $this->validate(['zoneName' => ['required', 'string', 'max:255'], 'countries' => ['required', 'string']]);
        ShippingZone::create(['store_id' => $this->currentStore()->getKey(), 'name' => $validated['zoneName'], 'countries_json' => collect(explode(',', $validated['countries']))->map(fn (string $country): string => mb_strtoupper(trim($country)))->filter()->values()->all(), 'regions_json' => []]);
        $this->reset('zoneName', 'countries');
        $this->toast('Shipping zone saved.');
    }

    public function addRate(): void
    {
        $this->authorizeWrite();
        $validated = $this->validate(['activeZoneId' => ['required', 'integer'], 'rateName' => ['required', 'string', 'max:255'], 'rateAmount' => ['required', 'numeric', 'min:0']]);
        $zone = ShippingZone::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($validated['activeZoneId']);
        $zone->rates()->create(['name' => $validated['rateName'], 'type' => 'flat', 'config_json' => ['amount' => (int) round((float) $validated['rateAmount'] * 100)], 'is_active' => true]);
        $this->reset('rateName', 'rateAmount');
        $this->toast('Shipping rate saved.');
    }

    public function deleteZone(int $id): void
    {
        $this->authorizeWrite();
        ShippingZone::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id)->delete();
        $this->toast('Shipping zone deleted.');
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::query()->where('store_id', $this->currentStore()->getKey())->with('rates')->orderBy('name')->get();
    }

    private function authorizeWrite(): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
    }

    public function render()
    {
        return view('livewire.admin.settings.shipping');
    }
}
