<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use App\Support\Storefront\PriceFormatter;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shipping settings page (spec 03 section 11.3): zone cards with nested rate
 * tables, zone/rate modals, and the test-address tool.
 */
#[Layout('layouts::admin')]
class Shipping extends Component
{
    use AuthorizesRequests, SendsToasts;

    /** ISO country codes offered in the zone checklist. */
    public const array COUNTRIES = [
        'AT' => 'Austria',
        'AU' => 'Australia',
        'BE' => 'Belgium',
        'CA' => 'Canada',
        'CH' => 'Switzerland',
        'CZ' => 'Czechia',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'ES' => 'Spain',
        'FI' => 'Finland',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'LU' => 'Luxembourg',
        'NL' => 'Netherlands',
        'NO' => 'Norway',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'SE' => 'Sweden',
        'US' => 'United States',
    ];

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    /** @var list<string> */
    public array $zoneCountries = [];

    public string $zoneRegions = '';

    public ?int $editingRateId = null;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public string $rateFlatAmount = '';

    /**
     * Range rows for weight and price based rates.
     *
     * @var list<array{min: string, max: string, amount: string}>
     */
    public array $rateRanges = [];

    public bool $rateActive = true;

    /** @var array{country_code: string, province_code: string, city: string, postal_code: string} */
    public array $testAddress = [
        'country_code' => 'DE',
        'province_code' => '',
        'city' => '',
        'postal_code' => '',
    ];

    /** @var array{zone: string, rates: list<string>}|false|null */
    public array|false|null $testResult = null;

    public function mount(): void
    {
        $this->authorize('viewSettings', $this->store());
    }

    /*
    |--------------------------------------------------------------------------
    | Zones
    |--------------------------------------------------------------------------
    */

    public function openZoneModal(?int $zoneId = null): void
    {
        $this->authorize('updateSettings', $this->store());
        $this->resetErrorBag();

        $this->editingZoneId = $zoneId;

        if ($zoneId !== null) {
            $zone = ShippingZone::query()->findOrFail($zoneId);

            $this->zoneName = $zone->name;
            $this->zoneCountries = $zone->countries_json ?? [];
            $this->zoneRegions = implode(', ', $zone->regions_json ?? []);
        } else {
            $this->zoneName = '';
            $this->zoneCountries = [];
            $this->zoneRegions = '';
        }

        Flux::modal('zone-form')->show();
    }

    public function saveZone(): void
    {
        $this->authorize('updateSettings', $this->store());

        $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'zoneCountries' => ['required', 'array', 'min:1'],
            'zoneCountries.*' => ['string', 'size:2'],
        ]);

        $attributes = [
            'name' => $this->zoneName,
            'countries_json' => array_values(array_map('strtoupper', $this->zoneCountries)),
            'regions_json' => $this->parsedRegions(),
        ];

        if ($this->editingZoneId !== null) {
            ShippingZone::query()->findOrFail($this->editingZoneId)->update($attributes);
        } else {
            ShippingZone::query()->create($attributes);
        }

        Flux::modal('zone-form')->close();

        $this->editingZoneId = null;
        $this->toast(__('Settings saved'));
    }

    public function deleteZone(int $zoneId): void
    {
        $this->authorize('updateSettings', $this->store());

        $zone = ShippingZone::query()->findOrFail($zoneId);

        $zone->rates()->delete();
        $zone->delete();

        $this->toast(__('Shipping zone deleted.'));
    }

    /*
    |--------------------------------------------------------------------------
    | Rates
    |--------------------------------------------------------------------------
    */

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        $this->authorize('updateSettings', $this->store());
        $this->resetErrorBag();

        ShippingZone::query()->findOrFail($zoneId);

        $this->rateZoneId = $zoneId;
        $this->editingRateId = $rateId;

        if ($rateId !== null) {
            $rate = ShippingRate::query()->whereRelation('zone', 'store_id', $this->store()->getKey())->findOrFail($rateId);

            $this->rateName = $rate->name;
            $this->rateType = $rate->type->value;
            $this->rateActive = $rate->is_active;
            $this->rateFlatAmount = $rate->type === ShippingRateType::Flat
                ? number_format(((int) ($rate->config_json['amount'] ?? 0)) / 100, 2, '.', '')
                : '';
            $this->rateRanges = $this->rangesFromConfig($rate);
        } else {
            $this->rateName = '';
            $this->rateType = 'flat';
            $this->rateFlatAmount = '';
            $this->rateRanges = [['min' => '', 'max' => '', 'amount' => '']];
            $this->rateActive = true;
        }

        Flux::modal('rate-form')->show();
    }

    public function addRateRange(): void
    {
        $this->rateRanges[] = ['min' => '', 'max' => '', 'amount' => ''];
    }

    public function removeRateRange(int $index): void
    {
        unset($this->rateRanges[$index]);
        $this->rateRanges = array_values($this->rateRanges);
    }

    public function saveRate(): void
    {
        $this->authorize('updateSettings', $this->store());

        $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', 'in:flat,weight,price'],
            'rateFlatAmount' => [$this->rateType === 'flat' ? 'required' : 'nullable', 'numeric', 'min:0'],
            'rateRanges' => [$this->rateType === 'flat' ? 'nullable' : 'required', 'array', ...($this->rateType === 'flat' ? [] : ['min:1'])],
            'rateRanges.*.min' => ['nullable', 'numeric', 'min:0'],
            'rateRanges.*.max' => ['nullable', 'numeric', 'min:0'],
            'rateRanges.*.amount' => [$this->rateType === 'flat' ? 'nullable' : 'required', 'numeric', 'min:0'],
        ]);

        $zone = ShippingZone::query()->findOrFail($this->rateZoneId);

        $attributes = [
            'name' => $this->rateName,
            'type' => $this->rateType,
            'config_json' => $this->buildRateConfig(),
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId !== null) {
            $rate = ShippingRate::query()->whereRelation('zone', 'store_id', $this->store()->getKey())->findOrFail($this->editingRateId);
            $rate->update($attributes + ['zone_id' => $zone->getKey()]);
        } else {
            $zone->rates()->create($attributes);
        }

        Flux::modal('rate-form')->close();

        $this->editingRateId = null;
        $this->toast(__('Shipping rate saved'));
    }

    public function deleteRate(int $rateId): void
    {
        $this->authorize('updateSettings', $this->store());

        ShippingRate::query()
            ->whereRelation('zone', 'store_id', $this->store()->getKey())
            ->findOrFail($rateId)
            ->delete();

        $this->toast(__('Shipping rate deleted.'));
    }

    public function toggleRateActive(int $rateId): void
    {
        $this->authorize('updateSettings', $this->store());

        $rate = ShippingRate::query()
            ->whereRelation('zone', 'store_id', $this->store()->getKey())
            ->findOrFail($rateId);

        $rate->update(['is_active' => ! $rate->is_active]);

        $this->toast(__('Settings saved'));
    }

    /*
    |--------------------------------------------------------------------------
    | Test address tool
    |--------------------------------------------------------------------------
    */

    public function testShippingAddress(): void
    {
        $calculator = app(ShippingCalculator::class);

        $rates = $calculator->getAvailableRates($this->store(), $this->testAddress);

        if ($rates->isEmpty()) {
            $this->testResult = false;

            return;
        }

        $firstRate = $rates->first();
        $zoneName = ShippingZone::query()->withoutGlobalScopes()->find($firstRate->zone_id)?->name ?? '';

        $this->testResult = [
            'zone' => $zoneName,
            'rates' => $rates
                ->map(fn (ShippingRate $rate): string => $rate->name.' - '.$this->describeRateConfig($rate))
                ->all(),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ShippingZone>
     */
    #[Computed]
    public function zones(): \Illuminate\Database\Eloquent\Collection
    {
        return ShippingZone::query()->with('rates')->orderBy('name')->get();
    }

    /**
     * Human readable summary of a rate's configuration for table display.
     */
    public function describeRateConfig(ShippingRate $rate): string
    {
        $currency = $this->store()->default_currency ?? 'EUR';

        if ($rate->type === ShippingRateType::Flat) {
            return PriceFormatter::format((int) ($rate->config_json['amount'] ?? 0), $currency);
        }

        $ranges = $rate->config_json['ranges'] ?? [];
        $isWeight = $rate->type === ShippingRateType::Weight;

        $parts = array_map(function (array $range) use ($currency, $isWeight): string {
            $min = (int) ($range[$isWeight ? 'min_g' : 'min_amount'] ?? 0);
            $max = $range[$isWeight ? 'max_g' : 'max_amount'] ?? null;
            $amount = PriceFormatter::format((int) ($range['amount'] ?? 0), $currency);

            $bounds = $isWeight
                ? $min.'g - '.($max !== null ? $max.'g' : '...')
                : PriceFormatter::format($min, $currency).' - '.($max !== null ? PriceFormatter::format((int) $max, $currency) : '...');

            return $bounds.': '.$amount;
        }, $ranges);

        return implode(' / ', $parts);
    }

    public function render(): View
    {
        return view('livewire.admin.settings.shipping')->title(__('Shipping'));
    }

    protected function store(): Store
    {
        return app('current_store');
    }

    /**
     * @return list<string>
     */
    protected function parsedRegions(): array
    {
        return collect(explode(',', $this->zoneRegions))
            ->map(fn (string $region): string => strtoupper(trim($region)))
            ->filter(fn (string $region): bool => $region !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRateConfig(): array
    {
        if ($this->rateType === 'flat') {
            return ['amount' => $this->toMinorUnits($this->rateFlatAmount)];
        }

        $isWeight = $this->rateType === 'weight';
        $minKey = $isWeight ? 'min_g' : 'min_amount';
        $maxKey = $isWeight ? 'max_g' : 'max_amount';

        $ranges = [];

        foreach ($this->rateRanges as $range) {
            if (trim((string) $range['amount']) === '') {
                continue;
            }

            $min = trim((string) $range['min']) !== ''
                ? ($isWeight ? (int) $range['min'] : $this->toMinorUnits((string) $range['min']))
                : 0;

            $max = trim((string) $range['max']) !== ''
                ? ($isWeight ? (int) $range['max'] : $this->toMinorUnits((string) $range['max']))
                : null;

            $row = [$minKey => $min, 'amount' => $this->toMinorUnits((string) $range['amount'])];

            if ($max !== null) {
                $row[$maxKey] = $max;
            }

            $ranges[] = $row;
        }

        return ['ranges' => $ranges];
    }

    /**
     * @return list<array{min: string, max: string, amount: string}>
     */
    protected function rangesFromConfig(ShippingRate $rate): array
    {
        if ($rate->type === ShippingRateType::Flat) {
            return [['min' => '', 'max' => '', 'amount' => '']];
        }

        $isWeight = $rate->type === ShippingRateType::Weight;
        $minKey = $isWeight ? 'min_g' : 'min_amount';
        $maxKey = $isWeight ? 'max_g' : 'max_amount';

        $ranges = array_map(function (array $range) use ($isWeight, $minKey, $maxKey): array {
            $format = fn (mixed $value): string => $value === null || $value === ''
                ? ''
                : ($isWeight ? (string) (int) $value : number_format(((int) $value) / 100, 2, '.', ''));

            return [
                'min' => $format($range[$minKey] ?? 0),
                'max' => $format($range[$maxKey] ?? null),
                'amount' => number_format(((int) ($range['amount'] ?? 0)) / 100, 2, '.', ''),
            ];
        }, $rate->config_json['ranges'] ?? []);

        return $ranges !== [] ? $ranges : [['min' => '', 'max' => '', 'amount' => '']];
    }

    protected function toMinorUnits(string $value): int
    {
        return (int) round((float) str_replace(',', '.', $value) * 100);
    }
}
