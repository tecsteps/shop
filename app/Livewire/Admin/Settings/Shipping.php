<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use App\Support\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Shipping extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    public string $zoneCountries = '';

    public ?int $rateZoneId = null;

    public ?int $editingRateId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public string $rateAmount = '0.00';

    public string $minimumWeight = '0';

    public string $maximumWeight = '';

    public string $minimumOrderAmount = '0.00';

    public string $maximumOrderAmount = '';

    public bool $rateActive = true;

    public string $testCountry = 'DE';

    public string $testRegion = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $testResult = null;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $this->storeId = $store->getKey();
    }

    public function editZone(int $zoneId): void
    {
        $zone = $this->zone($zoneId);

        $this->authorize('update', $this->scopedStore());

        $this->editingZoneId = $zone->getKey();
        $this->zoneName = $zone->name;
        $this->zoneCountries = implode(', ', $zone->countries_json ?? []);
    }

    public function saveZone(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'zoneCountries' => ['required', 'string', 'max:255'],
        ], [], [
            'zoneName' => 'zone name',
            'zoneCountries' => 'countries',
        ]);

        $countries = $this->countryCodes($validated['zoneCountries']);

        if ($countries === []) {
            $this->addError('zoneCountries', __('Enter at least one ISO country code.'));

            return;
        }

        $payload = [
            'store_id' => $store->getKey(),
            'name' => $validated['zoneName'],
            'countries_json' => $countries,
            'regions_json' => [],
        ];

        $this->editingZoneId
            ? $this->zone($this->editingZoneId)->update($payload)
            : ShippingZone::withoutGlobalScopes()->create($payload);

        $this->resetZoneForm();

        session()->flash('status', 'Shipping zone saved');
        $this->dispatch('toast', type: 'success', message: __('Shipping zone saved'));
    }

    public function deleteZone(int $zoneId): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->zone($zoneId)->delete();
        $this->resetZoneForm();
        $this->resetRateForm();

        session()->flash('status', 'Shipping zone deleted');
    }

    public function addRate(int $zoneId): void
    {
        $zone = $this->zone($zoneId);

        $this->authorize('update', $this->scopedStore());

        $this->resetRateForm();
        $this->rateZoneId = $zone->getKey();
    }

    public function editRate(int $rateId): void
    {
        $rate = $this->rate($rateId);

        $this->authorize('update', $this->scopedStore());

        $this->editingRateId = $rate->getKey();
        $this->rateZoneId = $rate->zone_id;
        $this->rateName = $rate->name;
        $this->rateType = $rate->type->value;
        $this->rateActive = $rate->is_active;
        $this->fillRateConfig($rate);
    }

    public function saveRate(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'rateZoneId' => ['required', 'integer'],
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', Rule::in(array_column(ShippingRateType::cases(), 'value'))],
            'rateAmount' => ['required', 'numeric', 'min:0'],
            'minimumWeight' => ['nullable', 'integer', 'min:0'],
            'maximumWeight' => ['nullable', 'integer', 'min:1'],
            'minimumOrderAmount' => ['nullable', 'numeric', 'min:0'],
            'maximumOrderAmount' => ['nullable', 'numeric', 'min:0'],
            'rateActive' => ['boolean'],
        ], [], [
            'rateZoneId' => 'shipping zone',
            'rateName' => 'rate name',
            'rateType' => 'rate type',
            'rateAmount' => 'rate amount',
        ]);

        $zone = $this->zone((int) $validated['rateZoneId']);
        $payload = [
            'zone_id' => $zone->getKey(),
            'name' => $validated['rateName'],
            'type' => ShippingRateType::from($validated['rateType']),
            'config_json' => $this->rateConfig(),
            'is_active' => $this->rateActive,
        ];

        $this->editingRateId
            ? $this->rate($this->editingRateId)->update($payload)
            : ShippingRate::withoutGlobalScopes()->create($payload);

        $this->resetRateForm();

        session()->flash('status', 'Shipping rate saved');
        $this->dispatch('toast', type: 'success', message: __('Shipping rate saved'));
    }

    public function deleteRate(int $rateId): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->rate($rateId)->delete();
        $this->resetRateForm();

        session()->flash('status', 'Shipping rate deleted');
    }

    public function toggleRateActive(int $rateId): void
    {
        $this->authorize('update', $this->scopedStore());

        $rate = $this->rate($rateId);
        $rate->forceFill(['is_active' => ! $rate->is_active])->save();
    }

    public function testShippingAddress(ShippingCalculator $shipping): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $address = [
            'country' => strtoupper($this->testCountry),
            'province_code' => strtoupper($this->testRegion),
        ];
        $zone = $shipping->matchingZone($store, $address);

        $this->testResult = [
            'zone' => $zone?->name,
            'rates' => $zone instanceof ShippingZone
                ? ShippingRate::withoutGlobalScopes()
                    ->where('zone_id', $zone->getKey())
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get()
                    ->map(fn (ShippingRate $rate): string => $rate->name.' - '.$this->rateSummary($rate))
                    ->all()
                : [],
        ];
    }

    /**
     * @return Collection<int, ShippingZone>
     */
    public function zones(): Collection
    {
        return ShippingZone::withoutGlobalScopes()
            ->with(['rates' => fn ($query) => $query->withoutGlobalScopes()->orderBy('id')])
            ->where('store_id', $this->storeId)
            ->orderBy('id')
            ->get();
    }

    public function rateSummary(ShippingRate $rate): string
    {
        if ($rate->type === ShippingRateType::Carrier) {
            return 'Carrier fallback '.Money::format((int) data_get($rate->config_json, 'amount', 0), $this->scopedStore()->default_currency);
        }

        $amount = match ($rate->type) {
            ShippingRateType::Flat => (int) data_get($rate->config_json, 'amount', 0),
            default => (int) data_get($rate->config_json, 'ranges.0.amount', 0),
        };

        return Money::format($amount, $this->scopedStore()->default_currency);
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.shipping', [
            'zones' => $this->zones(),
        ])->layout('layouts.app', [
            'title' => __('Shipping'),
        ]);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->whereKey($this->storeId)->firstOrFail();
    }

    private function zone(int $zoneId): ShippingZone
    {
        return ShippingZone::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey($zoneId)
            ->firstOrFail();
    }

    private function rate(int $rateId): ShippingRate
    {
        return ShippingRate::withoutGlobalScopes()
            ->whereKey($rateId)
            ->whereHas('zone', function ($query): void {
                $query->withoutGlobalScopes()->where('store_id', $this->storeId);
            })
            ->firstOrFail();
    }

    /**
     * @return list<string>
     */
    private function countryCodes(string $countries): array
    {
        return collect(explode(',', $countries))
            ->map(fn (string $country): string => strtoupper(trim($country)))
            ->filter(fn (string $country): bool => preg_match('/^[A-Z]{2}$/', $country) === 1)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function rateConfig(): array
    {
        $amount = Money::fromDecimalString($this->rateAmount);

        return match ($this->rateType) {
            ShippingRateType::Weight->value => [
                'ranges' => [[
                    'min_g' => (int) ($this->minimumWeight ?: 0),
                    'max_g' => $this->maximumWeight !== '' ? (int) $this->maximumWeight : null,
                    'amount' => $amount,
                ]],
            ],
            ShippingRateType::Price->value => [
                'ranges' => [[
                    'min_amount' => Money::fromDecimalString($this->minimumOrderAmount),
                    'max_amount' => $this->maximumOrderAmount !== '' ? Money::fromDecimalString($this->maximumOrderAmount) : null,
                    'amount' => $amount,
                ]],
            ],
            ShippingRateType::Carrier->value => ['amount' => $amount],
            default => ['amount' => $amount],
        };
    }

    private function fillRateConfig(ShippingRate $rate): void
    {
        $amount = match ($rate->type) {
            ShippingRateType::Flat, ShippingRateType::Carrier => (int) data_get($rate->config_json, 'amount', 0),
            default => (int) data_get($rate->config_json, 'ranges.0.amount', 0),
        };

        $this->rateAmount = number_format($amount / 100, 2, '.', '');
        $this->minimumWeight = (string) data_get($rate->config_json, 'ranges.0.min_g', 0);
        $this->maximumWeight = data_get($rate->config_json, 'ranges.0.max_g') !== null
            ? (string) data_get($rate->config_json, 'ranges.0.max_g')
            : '';
        $this->minimumOrderAmount = number_format(((int) data_get($rate->config_json, 'ranges.0.min_amount', 0)) / 100, 2, '.', '');
        $this->maximumOrderAmount = data_get($rate->config_json, 'ranges.0.max_amount') !== null
            ? number_format(((int) data_get($rate->config_json, 'ranges.0.max_amount')) / 100, 2, '.', '')
            : '';
    }

    private function resetZoneForm(): void
    {
        $this->editingZoneId = null;
        $this->zoneName = '';
        $this->zoneCountries = '';
    }

    private function resetRateForm(): void
    {
        $this->editingRateId = null;
        $this->rateZoneId = null;
        $this->rateName = '';
        $this->rateType = 'flat';
        $this->rateAmount = '0.00';
        $this->minimumWeight = '0';
        $this->maximumWeight = '';
        $this->minimumOrderAmount = '0.00';
        $this->maximumOrderAmount = '';
        $this->rateActive = true;
    }
}
