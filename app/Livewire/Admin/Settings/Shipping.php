<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Shipping settings')]
class Shipping extends Component
{
    public ?int $editingZoneId = null;

    public string $zoneName = '';

    public string $zoneCountries = '';

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public int $ratePrice = 0;

    public bool $rateActive = true;

    public function saveZone(): void
    {
        Gate::authorize('update', app('current_store'));
        $validated = $this->validate(['zoneName' => ['required', 'string', 'max:255'], 'zoneCountries' => ['required', 'string']]);
        $data = ['store_id' => app('current_store')->id, 'name' => $validated['zoneName'], 'countries_json' => array_values(array_filter(array_map(fn ($country) => strtoupper(trim($country)), explode(',', $validated['zoneCountries'])))), 'regions_json' => []];
        $this->editingZoneId ? ShippingZone::query()->findOrFail($this->editingZoneId)->update($data) : ShippingZone::query()->create($data);
        $this->reset('editingZoneId', 'zoneName', 'zoneCountries');
    }

    public function saveRate(): void
    {
        Gate::authorize('update', app('current_store'));
        $validated = $this->validate(['rateZoneId' => ['required', Rule::exists('shipping_zones', 'id')->where('store_id', app('current_store')->id)], 'rateName' => ['required', 'string', 'max:255'], 'rateType' => ['required', Rule::in(['flat', 'weight', 'price', 'carrier'])], 'ratePrice' => ['required', 'integer', 'min:0'], 'rateActive' => ['boolean']]);
        ShippingRate::query()->create([
            'zone_id' => $validated['rateZoneId'],
            'name' => $validated['rateName'],
            'type' => $validated['rateType'],
            'config_json' => [
                'amount' => $validated['ratePrice'],
                'currency' => app('current_store')->default_currency,
            ],
            'is_active' => $validated['rateActive'],
        ]);
        $this->reset('rateZoneId', 'rateName', 'ratePrice');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.shipping', ['zones' => ShippingZone::query()->with('rates')->get()]);
    }
}
