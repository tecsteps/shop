<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Shipping extends Component
{
    use ResolvesAdminStore;

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    /** @var array<int, string> */
    public array $zoneCountries = [];

    public ?int $editingRateId = null;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public string $ratePrice = '';

    public string $rateMinWeight = '';

    public string $rateMaxWeight = '';

    public string $rateMinAmount = '';

    public string $rateMaxAmount = '';

    public bool $rateActive = true;

    public string $testCountry = 'DE';

    public string $testState = '';

    public string $testCity = '';

    public string $testZip = '';

    /** @var array<string, mixed>|null */
    public ?array $testResult = null;

    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', ShippingZone::class);
    }

    public function openZoneModal(?int $zoneId = null): void
    {
        Gate::authorize('viewAny', ShippingZone::class);

        $this->editingZoneId = $zoneId;

        if ($zoneId !== null) {
            $zone = $this->findZone($zoneId);
            $this->zoneName = $zone->name;
            $this->zoneCountries = array_map('strval', (array) ($zone->countries_json ?? []));
        } else {
            $this->zoneName = '';
            $this->zoneCountries = [];
        }

        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        if ($this->editingZoneId !== null) {
            Gate::authorize('update', $this->findZone($this->editingZoneId));
        } else {
            Gate::authorize('create', ShippingZone::class);
        }

        $validated = $this->validate([
            'zoneName' => 'required|string|max:255',
            'zoneCountries' => 'required|array|min:1',
            'zoneCountries.*' => 'string|size:2',
        ]);

        $data = [
            'name' => $validated['zoneName'],
            'countries_json' => array_map('strtoupper', $validated['zoneCountries']),
            'regions_json' => [],
        ];

        if ($this->editingZoneId !== null) {
            $this->findZone($this->editingZoneId)->update($data);
        } else {
            ShippingZone::query()->create(['store_id' => $this->store()->getKey(), ...$data]);
        }

        $this->showZoneModal = false;
        $this->toast('success', 'Shipping zone saved.');
    }

    public function deleteZone(int $zoneId): void
    {
        $zone = $this->findZone($zoneId);

        Gate::authorize('delete', $zone);

        $zone->shippingRates()->delete();
        $zone->delete();

        $this->toast('success', 'Shipping zone deleted.');
    }

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        Gate::authorize('viewAny', ShippingZone::class);

        $this->rateZoneId = $zoneId;
        $this->editingRateId = $rateId;

        if ($rateId !== null) {
            $rate = $this->findRate($zoneId, $rateId);
            $config = is_array($rate->config_json) ? $rate->config_json : [];
            $this->rateName = $rate->name;
            $this->rateType = $rate->type->value;
            $this->ratePrice = isset($config['amount']) ? number_format(((int) $config['amount']) / 100, 2, '.', '') : '';
            $this->rateMinWeight = isset($config['min_weight_g']) ? (string) $config['min_weight_g'] : '';
            $this->rateMaxWeight = isset($config['max_weight_g']) ? (string) $config['max_weight_g'] : '';
            $this->rateMinAmount = isset($config['min_amount']) ? number_format(((int) $config['min_amount']) / 100, 2, '.', '') : '';
            $this->rateMaxAmount = isset($config['max_amount']) ? number_format(((int) $config['max_amount']) / 100, 2, '.', '') : '';
            $this->rateActive = (bool) $rate->is_active;
        } else {
            $this->rateName = '';
            $this->rateType = 'flat';
            $this->ratePrice = '';
            $this->rateMinWeight = '';
            $this->rateMaxWeight = '';
            $this->rateMinAmount = '';
            $this->rateMaxAmount = '';
            $this->rateActive = true;
        }

        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $zone = $this->findZone((int) $this->rateZoneId);

        Gate::authorize('update', $zone);

        $validated = $this->validate([
            'rateName' => 'required|string|max:255',
            'rateType' => 'required|in:flat,weight,price,carrier',
            'ratePrice' => 'required_unless:rateType,carrier|nullable|numeric|min:0',
        ]);

        $config = [];

        if ($validated['rateType'] !== 'carrier') {
            $config['amount'] = (int) round(((float) $validated['ratePrice']) * 100);
        }

        if ($validated['rateType'] === 'weight') {
            $config['min_weight_g'] = $this->rateMinWeight !== '' ? (int) $this->rateMinWeight : 0;
            $config['max_weight_g'] = $this->rateMaxWeight !== '' ? (int) $this->rateMaxWeight : null;
        }

        if ($validated['rateType'] === 'price') {
            $config['min_amount'] = $this->rateMinAmount !== '' ? (int) round(((float) $this->rateMinAmount) * 100) : 0;
            $config['max_amount'] = $this->rateMaxAmount !== '' ? (int) round(((float) $this->rateMaxAmount) * 100) : null;
        }

        $data = [
            'name' => $validated['rateName'],
            'type' => ShippingRateType::from($validated['rateType']),
            'config_json' => $config,
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId !== null) {
            $this->findRate($zone->getKey(), $this->editingRateId)->update($data);
        } else {
            $zone->shippingRates()->create($data);
        }

        $this->showRateModal = false;
        $this->toast('success', 'Shipping rate saved.');
    }

    public function deleteRate(int $rateId): void
    {
        $rate = ShippingRate::query()->whereKey($rateId)->firstOrFail();
        $zone = $this->findZone($rate->zone_id);

        Gate::authorize('update', $zone);

        $rate->delete();

        $this->toast('success', 'Shipping rate deleted.');
    }

    public function toggleRateActive(int $rateId): void
    {
        $rate = ShippingRate::query()->whereKey($rateId)->firstOrFail();
        $zone = $this->findZone($rate->zone_id);

        Gate::authorize('update', $zone);

        $rate->update(['is_active' => ! $rate->is_active]);
    }

    public function testShippingAddress(): void
    {
        Gate::authorize('viewAny', ShippingZone::class);

        $zone = app(ShippingCalculator::class)->getMatchingZone($this->store(), [
            'country_code' => $this->testCountry,
            'province_code' => $this->testState,
            'city' => $this->testCity,
            'zip' => $this->testZip,
        ]);

        if ($zone === null) {
            $this->testResult = ['matched' => false];
        } else {
            $this->testResult = [
                'matched' => true,
                'zone_name' => $zone->name,
                'rates' => $zone->shippingRates()->where('is_active', true)->get()
                    ->map(fn (ShippingRate $rate): array => [
                        'name' => $rate->name,
                        'price' => $this->money((int) (is_array($rate->config_json) ? ($rate->config_json['amount'] ?? 0) : 0)),
                    ])->all(),
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.shipping', [
            'zones' => ShippingZone::query()
                ->where('store_id', $this->store()->getKey())
                ->with(['shippingRates' => fn ($query) => $query->orderBy('id')])
                ->orderBy('id')
                ->get(),
            'countries' => $this->countryList(),
        ])->layout('livewire.admin.layout.app', ['title' => 'Shipping settings']);
    }

    protected function findZone(int $zoneId): ShippingZone
    {
        return ShippingZone::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($zoneId)
            ->firstOrFail();
    }

    protected function findRate(int $zoneId, int $rateId): ShippingRate
    {
        $this->findZone($zoneId);

        return ShippingRate::query()
            ->where('zone_id', $zoneId)
            ->whereKey($rateId)
            ->firstOrFail();
    }

    /**
     * @return array<string, string> ISO code => name.
     */
    protected function countryList(): array
    {
        return [
            'DE' => 'Germany', 'AT' => 'Austria', 'FR' => 'France', 'IT' => 'Italy',
            'ES' => 'Spain', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'PL' => 'Poland',
            'GB' => 'United Kingdom', 'US' => 'United States', 'CA' => 'Canada', 'AU' => 'Australia',
            'CH' => 'Switzerland', 'CZ' => 'Czechia', 'DK' => 'Denmark', 'SE' => 'Sweden',
            'NO' => 'Norway', 'FI' => 'Finland', 'IE' => 'Ireland', 'PT' => 'Portugal',
        ];
    }
}
