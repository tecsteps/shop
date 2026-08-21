<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Livewire\Component;

class Shipping extends Component
{
    public ?ShippingZone $editingZone = null;

    public string $zoneName = '';

    /** @var array<int, string> */
    public array $zoneCountries = [];

    public ?ShippingRate $editingRate = null;

    public ?int $editingRateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    /** @var array<string, int|string|null> */
    public array $rateConfig = ['price' => 0, 'min' => null, 'max' => null];

    public bool $rateActive = true;

    /** @var array<string, string> */
    public array $testAddress = ['country_code' => 'DE', 'province_code' => '', 'city' => '', 'postal_code' => ''];

    /** @var array<string, mixed>|null */
    public ?array $testResult = null;

    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public string $message = '';

    public function mount(): void
    {
        $this->authorize('update', app('current_store'));
    }

    public function openZoneModal(?int $zoneId = null): void
    {
        $this->authorize('update', app('current_store'));
        $this->resetValidation();
        $this->editingZone = $zoneId === null ? null : $this->storeZones()->findOrFail($zoneId);
        $this->zoneName = (string) ($this->editingZone?->name ?? '');
        $this->zoneCountries = array_map('strtoupper', $this->editingZone?->countries_json ?? []);
        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $this->authorize('update', app('current_store'));
        $data = $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'zoneCountries' => ['array'],
            'zoneCountries.*' => ['string', 'size:2'],
        ]);
        $attributes = [
            'name' => $data['zoneName'],
            'countries_json' => array_values(array_unique(array_map('strtoupper', $data['zoneCountries']))),
            'regions_json' => $this->editingZone?->regions_json ?? [],
        ];

        if ($this->editingZone === null) {
            ShippingZone::create(['store_id' => app('current_store')->getKey()] + $attributes);
        } else {
            $this->editingZone->update($attributes);
        }

        $this->showZoneModal = false;
        $this->message = 'Shipping zone saved.';
    }

    public function deleteZone(int $zoneId): void
    {
        $this->authorize('update', app('current_store'));
        $this->storeZones()->findOrFail($zoneId)->delete();
        $this->message = 'Shipping zone deleted.';
    }

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        $this->authorize('update', app('current_store'));
        $zone = $this->storeZones()->findOrFail($zoneId);
        $this->resetValidation();
        $this->editingRateZoneId = $zone->id;
        $this->editingRate = $rateId === null ? null : $zone->rates()->findOrFail($rateId);
        $this->rateName = (string) ($this->editingRate?->name ?? '');
        $this->rateType = (string) ($this->editingRate?->type ?? 'flat');
        $config = $this->editingRate?->config_json ?? [];
        $range = $config['ranges'][0] ?? [];
        $this->rateConfig = [
            'price' => (int) ($this->editingRate?->price_amount ?? $range['amount'] ?? 0),
            'min' => $range['min_g'] ?? $range['min_amount'] ?? null,
            'max' => $range['max_g'] ?? $range['max_amount'] ?? null,
        ];
        $this->rateActive = $this->editingRate?->is_active ?? true;
        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->authorize('update', app('current_store'));
        $data = $this->validate([
            'editingRateZoneId' => ['required', 'integer'],
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', 'in:flat,weight,price,carrier'],
            'rateConfig.price' => ['nullable', 'integer', 'min:0'],
            'rateConfig.min' => ['nullable', 'integer', 'min:0'],
            'rateConfig.max' => ['nullable', 'integer', 'gte:rateConfig.min'],
            'rateActive' => ['boolean'],
        ]);

        if ($data['rateType'] !== 'carrier' && ($data['rateConfig']['price'] ?? null) === null) {
            $this->addError('rateConfig.price', 'Enter a price for this rate.');

            return;
        }

        $data['rateConfig']['price'] ??= 0;
        $zone = $this->storeZones()->findOrFail($data['editingRateZoneId']);
        $config = [];

        if (in_array($data['rateType'], ['weight', 'price'], true)) {
            $config['ranges'] = [[
                $data['rateType'] === 'weight' ? 'min_g' : 'min_amount' => $data['rateConfig']['min'] ?? 0,
                $data['rateType'] === 'weight' ? 'max_g' : 'max_amount' => $data['rateConfig']['max'] ?? PHP_INT_MAX,
                'amount' => $data['rateConfig']['price'],
            ]];
        }
        $attributes = [
            'name' => $data['rateName'],
            'type' => $data['rateType'],
            'price_amount' => $data['rateConfig']['price'],
            'currency' => app('current_store')->default_currency,
            'config_json' => $config,
            'is_active' => (bool) $data['rateActive'],
        ];

        if ($this->editingRate === null) {
            $zone->rates()->create($attributes);
        } else {
            $this->editingRate->update($attributes);
        }

        $this->showRateModal = false;
        $this->message = 'Shipping rate saved.';
    }

    public function deleteRate(int $rateId): void
    {
        $this->authorize('update', app('current_store'));
        $rate = ShippingRate::query()->whereHas('zone', fn ($query) => $query->where('store_id', app('current_store')->getKey()))->findOrFail($rateId);
        $rate->delete();
        $this->message = 'Shipping rate deleted.';
    }

    public function toggleRate(int $rateId): void
    {
        $this->authorize('update', app('current_store'));
        $rate = ShippingRate::query()->whereHas('zone', fn ($query) => $query->where('store_id', app('current_store')->getKey()))->findOrFail($rateId);
        $rate->update(['is_active' => ! $rate->is_active]);
    }

    public function testShippingAddress(ShippingCalculator $shipping): void
    {
        $this->authorize('update', app('current_store'));
        $data = $this->validate([
            'testAddress.country_code' => ['required', 'string', 'size:2'],
            'testAddress.province_code' => ['nullable', 'string', 'max:10'],
            'testAddress.city' => ['nullable', 'string', 'max:255'],
            'testAddress.postal_code' => ['nullable', 'string', 'max:30'],
        ]);
        $rates = $shipping->getAvailableRates(app('current_store'), $data['testAddress']);
        $this->testResult = [
            'zone' => $rates->first()?->zone?->name,
            'rates' => $rates->map(fn (ShippingRate $rate): array => ['name' => $rate->name, 'price_amount' => $rate->price_amount, 'currency' => $rate->currency])->values()->all(),
        ];
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.shipping', [
            'zones' => $this->storeZones()->with('rates')->latest()->get(),
            'countries' => $this->countryOptions(),
        ])->layout('layouts.admin');
    }

    private function storeZones(): \Illuminate\Database\Eloquent\Builder
    {
        return ShippingZone::query()->where('store_id', app('current_store')->getKey());
    }

    /** @return array<string, string> */
    private function countryOptions(): array
    {
        return [
            'AU' => 'Australia', 'AT' => 'Austria', 'BE' => 'Belgium', 'BR' => 'Brazil', 'CA' => 'Canada', 'CH' => 'Switzerland', 'CN' => 'China', 'DE' => 'Germany', 'DK' => 'Denmark', 'ES' => 'Spain', 'FI' => 'Finland', 'FR' => 'France', 'GB' => 'United Kingdom', 'IE' => 'Ireland', 'IN' => 'India', 'IT' => 'Italy', 'JP' => 'Japan', 'LU' => 'Luxembourg', 'MX' => 'Mexico', 'NL' => 'Netherlands', 'NO' => 'Norway', 'NZ' => 'New Zealand', 'PL' => 'Poland', 'PT' => 'Portugal', 'SE' => 'Sweden', 'SG' => 'Singapore', 'US' => 'United States',
        ];
    }
}
