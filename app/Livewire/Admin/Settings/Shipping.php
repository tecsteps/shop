<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Shipping extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public bool $showZoneModal = false;

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    /** @var list<string> */
    public array $zoneCountries = [];

    public bool $showRateModal = false;

    public ?int $rateZoneId = null;

    public ?int $editingRateId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    /** @var array<string, mixed> */
    public array $rateConfig = [
        'price' => null,
        'min_weight_g' => null,
        'max_weight_g' => null,
        'min_amount' => null,
        'max_amount' => null,
    ];

    public bool $rateActive = true;

    /** @var array<int, bool> */
    public array $rateActiveStates = [];

    public string $testCountry = 'US';

    public string $testState = '';

    public string $testCity = '';

    public string $testZip = '';

    /** @var array{zone: string, rates: list<array{name: string, summary: string}>}|null */
    public ?array $testResult = null;

    public bool $testNoMatch = false;

    public function mount(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $this->rateActiveStates = app('current_store')
            ->shippingZones()
            ->with('rates')
            ->get()
            ->flatMap(fn ($zone) => $zone->rates->pluck('is_active', 'id')->map(fn ($value) => (bool) $value))
            ->all();
    }

    public function updatedRateActiveStates(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        foreach ($this->rateActiveStates as $rateId => $active) {
            ShippingRate::where('id', $rateId)
                ->whereHas('zone', fn ($q) => $q->where('store_id', app('current_store')->id))
                ->update(['is_active' => (bool) $active]);
        }

        $this->toast('Shipping rates updated');
    }

    #[Computed]
    public function zones(): Collection
    {
        return app('current_store')->shippingZones()->with('rates')->orderBy('name')->get();
    }

    public function openZoneModal(?int $zoneId = null): void
    {
        $this->editingZoneId = $zoneId;
        $this->zoneName = '';
        $this->zoneCountries = [];

        if ($zoneId) {
            $zone = ShippingZone::find($zoneId);

            if ($zone) {
                $this->zoneName = $zone->name;
                $this->zoneCountries = $zone->countries_json ?? [];
            }
        }

        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'zoneCountries' => ['required', 'array', 'min:1'],
        ]);

        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'name' => $this->zoneName,
            'countries_json' => array_values($this->zoneCountries),
            'regions_json' => [],
        ];

        if ($this->editingZoneId) {
            ShippingZone::where('id', $this->editingZoneId)->where('store_id', $store->id)->update($data);
        } else {
            ShippingZone::create($data);
        }

        $this->showZoneModal = false;
        $this->toast('Shipping zone saved');
    }

    public function deleteZone(int $zoneId): void
    {
        $this->authorize('updateSettings', app('current_store'));

        ShippingZone::where('id', $zoneId)->where('store_id', app('current_store')->id)->delete();

        $this->toast('Shipping zone deleted');
    }

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        $this->rateZoneId = $zoneId;
        $this->editingRateId = $rateId;
        $this->rateName = '';
        $this->rateType = 'flat';
        $this->rateConfig = ['price' => null, 'min_weight_g' => null, 'max_weight_g' => null, 'min_amount' => null, 'max_amount' => null];
        $this->rateActive = true;

        if ($rateId) {
            $rate = ShippingRate::find($rateId);

            if ($rate) {
                $this->rateName = $rate->name;
                $this->rateType = $rate->type;
                $this->rateConfig = array_merge($this->rateConfig, $rate->config_json ?? []);
                $this->rateActive = $rate->is_active;
            }
        }

        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', 'in:flat,weight,price,carrier'],
            'rateConfig.price' => ['nullable', 'numeric', 'min:0'],
            'rateConfig.min_weight_g' => ['nullable', 'integer', 'min:0'],
            'rateConfig.max_weight_g' => ['nullable', 'integer', 'min:0'],
            'rateConfig.min_amount' => ['nullable', 'numeric', 'min:0'],
            'rateConfig.max_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $store = app('current_store');

        $config = collect($this->rateConfig)->filter(fn ($value) => $value !== null && $value !== '')->all();

        if (in_array($this->rateType, ['flat', 'price'], true) && isset($config['price'])) {
            $config['price'] = (int) round((float) $config['price'] * 100);
        }

        if (isset($config['min_amount'])) {
            $config['min_amount'] = (int) round((float) $config['min_amount'] * 100);
        }

        if (isset($config['max_amount'])) {
            $config['max_amount'] = (int) round((float) $config['max_amount'] * 100);
        }

        $data = [
            'zone_id' => $this->rateZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => $config,
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId) {
            ShippingRate::where('id', $this->editingRateId)
                ->whereHas('zone', fn ($q) => $q->where('store_id', $store->id))
                ->update($data);
        } else {
            ShippingRate::create($data);
        }

        $this->showRateModal = false;
        $this->toast('Shipping rate saved');
    }

    public function deleteRate(int $rateId): void
    {
        $this->authorize('updateSettings', app('current_store'));

        ShippingRate::where('id', $rateId)
            ->whereHas('zone', fn ($q) => $q->where('store_id', app('current_store')->id))
            ->delete();

        unset($this->rateActiveStates[$rateId]);

        $this->toast('Shipping rate deleted');
    }

    public function toggleRate(int $rateId): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $active = ! (bool) ($this->rateActiveStates[$rateId] ?? false);

        $this->rateActiveStates[$rateId] = $active;

        ShippingRate::where('id', $rateId)
            ->whereHas('zone', fn ($q) => $q->where('store_id', app('current_store')->id))
            ->update(['is_active' => $active]);
    }

    public function testShippingAddress(): void
    {
        $this->authorize('updateSettings', app('current_store'));

        $store = app('current_store');
        $calculator = app(ShippingCalculator::class);

        $zone = $calculator->getMatchingZone($store, [
            'country' => $this->testCountry,
            'state' => $this->testState,
            'city' => $this->testCity,
            'zip' => $this->testZip,
        ]);

        $this->testNoMatch = $zone === null;
        $this->testResult = null;

        if ($zone) {
            $this->testResult = [
                'zone' => $zone->name,
                'rates' => $zone->rates->where('is_active', true)->map(fn ($rate) => [
                    'name' => $rate->name,
                    'summary' => $this->rateSummary($rate),
                ])->values()->all(),
            ];
        }
    }

    public function rateSummary(ShippingRate $rate): string
    {
        $config = $rate->config_json ?? [];

        return match ($rate->type) {
            'flat' => $this->formatMoney((int) ($config['price'] ?? 0)),
            'weight' => ($config['min_weight_g'] ?? '?').'g–'.($config['max_weight_g'] ?? '∞').'g · '.$this->formatMoney((int) ($config['price'] ?? 0)),
            'price' => $this->formatMoney((int) ($config['min_amount'] ?? 0)).'–'.$this->formatMoney((int) ($config['max_amount'] ?? PHP_INT_MAX)),
            default => 'Carrier calculated',
        };
    }

    public function formatMoney(int $amount): string
    {
        $currency = app('current_store')->default_currency;

        return number_format($amount / 100, 2, '.', ',').' '.$currency;
    }

    public function render()
    {
        return view('livewire.admin.settings.shipping');
    }
}
