<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shipping settings: zones and their rates (CRUD), plus a test tool that
 * resolves a sample address against the configured zones/rates. Owners/admins.
 */
#[Layout('livewire.admin.layout.app')]
class Shipping extends Component
{
    use BindsCurrentStore;

    public bool $showZoneModal = false;

    public bool $showRateModal = false;

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    /** @var array<int, string> */
    public array $zoneCountries = [];

    public string $zoneCountriesInput = '';

    public ?int $editingRateId = null;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    /** @var array<string, mixed> */
    public array $rateConfig = ['price' => '', 'min' => '', 'max' => ''];

    public bool $rateActive = true;

    /** @var array<string, string> */
    public array $testAddress = ['country' => '', 'province_code' => '', 'city' => '', 'postal_code' => ''];

    /** @var array<string, mixed>|null */
    public ?array $testResult = null;

    public function mount(): void
    {
        $this->guard();
    }

    private function guard(): void
    {
        if (! Gate::allows('manage-shipping')) {
            abort(403);
        }
    }

    public function getZonesProperty()
    {
        return ShippingZone::query()->with('rates')->orderBy('name')->get();
    }

    public function openZoneModal(?int $zoneId = null): void
    {
        $this->resetValidation();
        $this->editingZoneId = $zoneId;

        if ($zoneId !== null) {
            $zone = ShippingZone::query()->findOrFail($zoneId);
            $this->zoneName = $zone->name;
            $this->zoneCountriesInput = implode(', ', $zone->countries_json ?? []);
        } else {
            $this->zoneName = '';
            $this->zoneCountriesInput = '';
        }

        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $this->guard();
        $this->validate(['zoneName' => ['required', 'string', 'max:255']]);

        $countries = collect(explode(',', $this->zoneCountriesInput))
            ->map(fn (string $c): string => strtoupper(trim($c)))
            ->filter()
            ->values()
            ->all();

        $data = ['name' => $this->zoneName, 'countries_json' => $countries, 'regions_json' => []];

        if ($this->editingZoneId !== null) {
            ShippingZone::query()->findOrFail($this->editingZoneId)->update($data);
        } else {
            ShippingZone::create($data);
        }

        $this->showZoneModal = false;
        $this->dispatch('toast', type: 'success', message: __('Shipping zone saved'));
    }

    public function deleteZone(int $zoneId): void
    {
        $this->guard();
        ShippingZone::query()->whereKey($zoneId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Shipping zone deleted'));
    }

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        $this->resetValidation();
        $this->rateZoneId = $zoneId;
        $this->editingRateId = $rateId;

        if ($rateId !== null) {
            $rate = ShippingRate::query()->findOrFail($rateId);
            $this->rateName = $rate->name;
            $this->rateType = $rate->type->value;
            $config = $rate->config_json ?? [];
            $this->rateConfig = [
                'price' => isset($config['price']) ? number_format($config['price'] / 100, 2, '.', '') : '',
                'min' => $config['min'] ?? '',
                'max' => $config['max'] ?? '',
            ];
            $this->rateActive = (bool) $rate->is_active;
        } else {
            $this->rateName = '';
            $this->rateType = 'flat';
            $this->rateConfig = ['price' => '', 'min' => '', 'max' => ''];
            $this->rateActive = true;
        }

        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->guard();
        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', Rule::in(['flat', 'weight', 'price', 'carrier'])],
        ]);

        $config = [];
        if ($this->rateConfig['price'] !== '') {
            $config['price'] = (int) round(((float) $this->rateConfig['price']) * 100);
        }
        if ($this->rateType === 'weight' || $this->rateType === 'price') {
            $config['min'] = $this->rateConfig['min'] !== '' ? (int) $this->rateConfig['min'] : null;
            $config['max'] = $this->rateConfig['max'] !== '' ? (int) $this->rateConfig['max'] : null;
        }

        $data = [
            'zone_id' => $this->rateZoneId,
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => $config,
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId !== null) {
            ShippingRate::query()->findOrFail($this->editingRateId)->update($data);
        } else {
            ShippingRate::create($data);
        }

        $this->showRateModal = false;
        $this->dispatch('toast', type: 'success', message: __('Shipping rate saved'));
    }

    public function deleteRate(int $rateId): void
    {
        $this->guard();
        ShippingRate::query()->whereKey($rateId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Shipping rate deleted'));
    }

    public function testShippingAddress(ShippingCalculator $calculator): void
    {
        $store = app('current_store');
        $zone = $calculator->getMatchingZone($store, $this->testAddress);

        if ($zone === null) {
            $this->testResult = ['matched' => false];

            return;
        }

        $this->testResult = [
            'matched' => true,
            'zone' => $zone->name,
            'rates' => $zone->rates->where('is_active', true)->map(fn (ShippingRate $r): array => [
                'name' => $r->name,
                'price' => $r->config_json['price'] ?? 0,
            ])->values()->all(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.settings.shipping');
    }
}
