<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Shipping extends Component
{
    public string $zoneName = '';

    public string $countries = 'DE';

    public string $regions = '';

    public ?int $editingZoneId = null;

    public ?int $zoneId = null;

    public string $rateName = 'Standard Shipping';

    public int $rateAmount = 499;

    public ?int $minOrderAmount = null;

    public ?int $maxOrderAmount = null;

    public ?int $editingRateId = null;

    public string $rateType = 'flat';

    public bool $rateActive = true;

    public string $carrier = 'ups';

    public string $carrierService = 'ground';

    /** @var list<array{min_weight_g: int, max_weight_g: ?int, price_amount: int}> */
    public array $weightTiers = [['min_weight_g' => 0, 'max_weight_g' => null, 'price_amount' => 499]];

    /** @var list<array{min_order_amount: int, max_order_amount: ?int, price_amount: int}> */
    public array $priceTiers = [['min_order_amount' => 0, 'max_order_amount' => null, 'price_amount' => 499]];

    public string $testCountryCode = 'DE';

    public string $testRegionCode = '';

    public string $testCity = '';

    public string $testPostalCode = '';

    public int $testSubtotalAmount = 0;

    public int $testWeightGrams = 0;

    /** @var array{zone: string, rates: list<array{name: string, type: string, price_amount: int}>}|null */
    public ?array $testResult = null;

    public bool $hasTestedAddress = false;

    public function saveZone(): void
    {
        Gate::authorize('manage-shipping');
        $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'countries' => ['required', 'string', 'max:500'],
            'regions' => ['nullable', 'string', 'max:500'],
        ]);
        $countryCodes = array_values(array_filter(array_unique(array_map(static fn (string $code): string => strtoupper(trim($code)), explode(',', $this->countries)))));

        if ($countryCodes === []) {
            throw ValidationException::withMessages(['countries' => 'Add at least one country code.']);
        }

        foreach ($countryCodes as $countryCode) {
            validator(['country' => $countryCode], ['country' => ['required', 'size:2']])->validate();
        }

        $regionCodes = $this->normalizeRegions($countryCodes);
        $zone = $this->editingZoneId === null
            ? new ShippingZone(['store_id' => app('current_store')->id])
            : ShippingZone::query()->findOrFail($this->editingZoneId);
        $zone->fill(['name' => $this->zoneName, 'countries' => $countryCodes, 'regions' => $regionCodes]);
        $zone->save();
        $this->zoneId = $zone->id;
        $this->editingZoneId = null;
        session()->flash('status', 'Shipping zone saved.');
    }

    public function editZone(int $zoneId): void
    {
        Gate::authorize('manage-shipping');
        $zone = ShippingZone::query()->findOrFail($zoneId);
        $this->editingZoneId = $zone->id;
        $this->zoneName = $zone->name;
        $this->countries = implode(', ', $zone->countries ?? []);
        $this->regions = implode(', ', $zone->regions ?? []);
    }

    public function cancelZoneEdit(): void
    {
        $this->editingZoneId = null;
        $this->zoneName = '';
        $this->countries = 'DE';
        $this->regions = '';
        $this->resetValidation();
    }

    public function saveRate(): void
    {
        Gate::authorize('manage-shipping');
        $rules = [
            'zoneId' => ['required', 'integer'],
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', 'in:flat,weight,price,carrier'],
            'rateActive' => ['boolean'],
            'minOrderAmount' => ['nullable', 'integer', 'min:0'],
            'maxOrderAmount' => ['nullable', 'integer', 'gte:minOrderAmount'],
        ];

        if (in_array($this->rateType, ['flat', 'carrier'], true)) {
            $rules['rateAmount'] = ['required', 'integer', 'min:0'];
        }

        if ($this->rateType === 'weight') {
            $rules['weightTiers'] = ['required', 'array', 'min:1', 'max:20'];
            $rules['weightTiers.*.min_weight_g'] = ['required', 'integer', 'min:0'];
            $rules['weightTiers.*.max_weight_g'] = ['nullable', 'integer', 'gt:weightTiers.*.min_weight_g'];
            $rules['weightTiers.*.price_amount'] = ['required', 'integer', 'min:0'];
        }

        if ($this->rateType === 'price') {
            $rules['priceTiers'] = ['required', 'array', 'min:1', 'max:20'];
            $rules['priceTiers.*.min_order_amount'] = ['required', 'integer', 'min:0'];
            $rules['priceTiers.*.max_order_amount'] = ['nullable', 'integer', 'gt:priceTiers.*.min_order_amount'];
            $rules['priceTiers.*.price_amount'] = ['required', 'integer', 'min:0'];
        }

        if ($this->rateType === 'carrier') {
            $rules['carrier'] = ['required', 'in:ups,fedex,dhl'];
            $rules['carrierService'] = ['required', 'string', 'max:80'];
        }

        $this->validate($rules);
        $zone = ShippingZone::query()->findOrFail($this->zoneId);
        $config = match ($this->rateType) {
            'flat' => ['price_amount' => $this->rateAmount],
            'weight' => ['tiers' => $this->validatedTiers($this->weightTiers, 'min_weight_g', 'max_weight_g')],
            'price' => ['tiers' => $this->validatedTiers($this->priceTiers, 'min_order_amount', 'max_order_amount')],
            'carrier' => ['carrier' => $this->carrier, 'service' => $this->carrierService, 'price_amount' => $this->rateAmount],
        };
        $attributes = [
            'name' => $this->rateName,
            'type' => $this->rateType,
            'price_amount' => in_array($this->rateType, ['flat', 'carrier'], true) ? $this->rateAmount : 0,
            'min_order_amount' => $this->minOrderAmount,
            'max_order_amount' => $this->maxOrderAmount,
            'config_json' => $config,
            'is_active' => $this->rateActive,
        ];
        $rate = $this->editingRateId === null
            ? $zone->rates()->make()
            : $this->findRateForCurrentStore($this->editingRateId);
        $rate->fill($attributes);
        $zone->rates()->save($rate);
        $this->editingRateId = null;
        $this->rateName = 'Standard Shipping';
        $this->rateType = 'flat';
        $this->rateAmount = 499;
        $this->rateActive = true;
        session()->flash('status', 'Shipping rate saved.');
    }

    public function editRate(int $rateId): void
    {
        Gate::authorize('manage-shipping');
        $rate = $this->findRateForCurrentStore($rateId);
        $this->zoneId = $rate->shipping_zone_id;
        $this->editingRateId = $rate->id;
        $this->rateName = $rate->name;
        $this->rateType = $rate->type;
        $this->rateAmount = (int) ($rate->config_json['price_amount'] ?? $rate->price_amount);
        $this->minOrderAmount = $rate->min_order_amount;
        $this->maxOrderAmount = $rate->max_order_amount;
        $this->rateActive = $rate->is_active;
        $this->carrier = $rate->config_json['carrier'] ?? 'ups';
        $this->carrierService = $rate->config_json['service'] ?? 'ground';
        $tiers = $rate->config_json['tiers'] ?? $rate->config_json['ranges'] ?? [];

        if ($rate->type === 'weight') {
            $this->weightTiers = collect($tiers)->map(static fn (array $tier): array => [
                'min_weight_g' => (int) ($tier['min_weight_g'] ?? $tier['min_g'] ?? 0),
                'max_weight_g' => isset($tier['max_weight_g']) ? (int) $tier['max_weight_g'] : (isset($tier['max_g']) ? (int) $tier['max_g'] : null),
                'price_amount' => (int) ($tier['price_amount'] ?? $tier['amount'] ?? 0),
            ])->values()->all() ?: [['min_weight_g' => 0, 'max_weight_g' => null, 'price_amount' => 499]];
        }

        if ($rate->type === 'price') {
            $this->priceTiers = collect($tiers)->map(static fn (array $tier): array => [
                'min_order_amount' => (int) ($tier['min_order_amount'] ?? $tier['min_amount'] ?? 0),
                'max_order_amount' => isset($tier['max_order_amount']) ? (int) $tier['max_order_amount'] : (isset($tier['max_amount']) ? (int) $tier['max_amount'] : null),
                'price_amount' => (int) ($tier['price_amount'] ?? $tier['amount'] ?? 0),
            ])->values()->all() ?: [['min_order_amount' => 0, 'max_order_amount' => null, 'price_amount' => 499]];
        }
    }

    public function prepareRate(int $zoneId): void
    {
        Gate::authorize('manage-shipping');
        ShippingZone::query()->findOrFail($zoneId);
        $this->cancelRateEdit();
        $this->zoneId = $zoneId;
    }

    public function cancelRateEdit(): void
    {
        $this->editingRateId = null;
        $this->rateName = 'Standard Shipping';
        $this->rateType = 'flat';
        $this->rateAmount = 499;
        $this->minOrderAmount = null;
        $this->maxOrderAmount = null;
        $this->rateActive = true;
        $this->resetValidation();
    }

    public function toggleRate(int $rateId): void
    {
        Gate::authorize('manage-shipping');
        $rate = $this->findRateForCurrentStore($rateId);
        $rate->update(['is_active' => ! $rate->is_active]);
    }

    public function deleteRate(int $rateId): void
    {
        Gate::authorize('manage-shipping');
        $this->findRateForCurrentStore($rateId)->delete();
    }

    public function addWeightTier(): void
    {
        $this->weightTiers[] = ['min_weight_g' => 0, 'max_weight_g' => null, 'price_amount' => 499];
    }

    public function removeWeightTier(int $index): void
    {
        unset($this->weightTiers[$index]);
        $this->weightTiers = array_values($this->weightTiers);
    }

    public function addPriceTier(): void
    {
        $this->priceTiers[] = ['min_order_amount' => 0, 'max_order_amount' => null, 'price_amount' => 499];
    }

    public function removePriceTier(int $index): void
    {
        unset($this->priceTiers[$index]);
        $this->priceTiers = array_values($this->priceTiers);
    }

    public function testShippingAddress(ShippingCalculator $calculator): void
    {
        Gate::authorize('manage-shipping');
        $this->validate([
            'testCountryCode' => ['required', 'string', 'size:2'],
            'testRegionCode' => ['nullable', 'string', 'max:10'],
            'testCity' => ['nullable', 'string', 'max:255'],
            'testPostalCode' => ['nullable', 'string', 'max:20'],
            'testSubtotalAmount' => ['required', 'integer', 'min:0'],
            'testWeightGrams' => ['required', 'integer', 'min:0'],
        ]);

        $address = ['country_code' => strtoupper($this->testCountryCode), 'province_code' => strtoupper($this->testRegionCode)];
        $zone = $calculator->matchingZone((int) app('current_store')->id, $address['country_code'], $address['province_code']);
        $rates = $calculator->ratesForAddress((int) app('current_store')->id, $address, $this->testSubtotalAmount, $this->testWeightGrams);
        $this->hasTestedAddress = true;
        $this->testResult = $zone === null ? null : [
            'zone' => $zone->name,
            'rates' => $rates->map(static fn (ShippingRate $rate): array => [
                'name' => $rate->name,
                'type' => $rate->type,
                'price_amount' => (int) $rate->price_amount,
            ])->all(),
        ];
    }

    public function deleteZone(int $zoneId): void
    {
        Gate::authorize('manage-shipping');
        ShippingZone::query()->findOrFail($zoneId)->delete();
    }

    public function render(): mixed
    {
        Gate::authorize('manage-shipping');

        return view('livewire.admin.settings.shipping', ['zones' => ShippingZone::query()->with('rates')->orderBy('name')->get()])->layout('layouts.admin');
    }

    /** @param list<string> $countryCodes
     * @return list<string>
     */
    private function normalizeRegions(array $countryCodes): array
    {
        $regions = array_values(array_filter(array_unique(array_map(static fn (string $code): string => strtoupper(trim($code)), explode(',', $this->regions)))));

        foreach ($regions as $index => $region) {
            if (preg_match('/\A[A-Z]{2}-[A-Z0-9]{1,3}\z/', $region) !== 1) {
                if (count($countryCodes) !== 1 || preg_match('/\A[A-Z0-9]{1,3}\z/', $region) !== 1) {
                    throw ValidationException::withMessages(['regions' => 'Use region codes like US-CA.']);
                }

                $regions[$index] = $countryCodes[0].'-'.$region;
            }

            [$countryCode] = explode('-', $regions[$index], 2);

            if (! in_array($countryCode, $countryCodes, true)) {
                throw ValidationException::withMessages(['regions' => 'Each region must belong to a selected country.']);
            }
        }

        return $regions;
    }

    /** @param list<array<string, int|null>> $tiers
     * @return list<array<string, int|null>>
     */
    private function validatedTiers(array $tiers, string $minimumKey, string $maximumKey): array
    {
        $normalized = array_map(static function (array $tier) use ($minimumKey, $maximumKey): array {
            $tier[$minimumKey] = (int) ($tier[$minimumKey] ?? 0);
            $tier[$maximumKey] = ($tier[$maximumKey] ?? null) === '' ? null : (($tier[$maximumKey] ?? null) === null ? null : (int) $tier[$maximumKey]);
            $tier['price_amount'] = (int) ($tier['price_amount'] ?? 0);

            return $tier;
        }, $tiers);
        $sorted = collect($normalized)->sortBy($minimumKey)->values()->all();

        foreach ($sorted as $index => $tier) {
            $maximum = $tier[$maximumKey] ?? null;

            if ($maximum !== null && $maximum <= $tier[$minimumKey]) {
                throw ValidationException::withMessages(["rateTiers.{$index}" => 'The maximum must be greater than the minimum.']);
            }

            if ($index < count($sorted) - 1 && ($maximum === null || $maximum >= $sorted[$index + 1][$minimumKey])) {
                throw ValidationException::withMessages(["rateTiers.{$index}" => 'Tier ranges cannot overlap and only the final tier can be open-ended.']);
            }
        }

        return $sorted;
    }

    private function findRateForCurrentStore(int $rateId): ShippingRate
    {
        return ShippingRate::query()->whereHas('zone', fn ($zones) => $zones->where('store_id', app('current_store')->id))->findOrFail($rateId);
    }
}
