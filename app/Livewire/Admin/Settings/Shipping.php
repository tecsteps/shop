<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Shipping extends Component
{
    public bool $showZoneForm = false;

    public ?int $editingZoneId = null;

    public string $zoneName = '';

    public string $zoneCountries = '';

    public string $zoneRegions = '';

    public bool $showRateForm = false;

    public ?int $editingRateId = null;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    public bool $rateActive = true;

    public ?int $rateAmount = null;

    /** @var list<array<string, int|string|null>> */
    public array $rateRanges = [];

    public function mount(): void
    {
        Gate::authorize('manage-shipping');
    }

    /**
     * Open the zone modal in create or edit mode (spec 03 §11.3).
     */
    public function openZoneForm(?int $zoneId = null): void
    {
        Gate::authorize('manage-shipping');

        $this->editingZoneId = $zoneId;

        if ($zoneId !== null) {
            $zone = ShippingZone::query()->find($zoneId);

            if ($zone === null) {
                return;
            }

            $this->zoneName = $zone->name;
            $this->zoneCountries = implode(', ', $zone->countries_json ?? []);
            $this->zoneRegions = implode(', ', $zone->regions_json ?? []);
        } else {
            $this->zoneName = '';
            $this->zoneCountries = '';
            $this->zoneRegions = '';
        }

        $this->showZoneForm = true;
    }

    /**
     * Create or update a shipping zone (spec 03 §11.3).
     */
    public function saveZone(): void
    {
        Gate::authorize('manage-shipping');

        /** @var Store $store */
        $store = app('current_store');

        $validated = $this->validate([
            'zoneName' => ['required', 'string', 'max:255'],
            'zoneCountries' => ['required', 'string', 'max:2000'],
            'zoneRegions' => ['nullable', 'string', 'max:2000'],
        ]);

        $countries = $this->parseCodeList($validated['zoneCountries']);

        if ($countries === []) {
            $this->addError('zoneCountries', 'Enter at least one ISO country code.');

            return;
        }

        $data = [
            'name' => $validated['zoneName'],
            'countries_json' => $countries,
            'regions_json' => $this->parseCodeList($validated['zoneRegions'] ?? ''),
        ];

        if ($this->editingZoneId !== null) {
            $zone = ShippingZone::query()->find($this->editingZoneId);

            if ($zone === null) {
                return;
            }

            $zone->update($data);
        } else {
            ShippingZone::create(array_merge($data, ['store_id' => $store->id]));
        }

        $this->showZoneForm = false;
        $this->editingZoneId = null;

        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved');
    }

    /**
     * Delete a zone with its rates (spec 03 §11.3).
     */
    public function deleteZone(int $zoneId): void
    {
        Gate::authorize('manage-shipping');

        $zone = ShippingZone::query()->find($zoneId);

        if ($zone === null) {
            return;
        }

        DB::transaction(function () use ($zone): void {
            $zone->rates()->delete();
            $zone->delete();
        });

        $this->dispatch('toast', type: 'success', message: 'Shipping zone deleted');
    }

    /**
     * Open the rate modal in create or edit mode (spec 03 §11.3).
     */
    public function openRateForm(int $zoneId, ?int $rateId = null): void
    {
        Gate::authorize('manage-shipping');

        $this->rateZoneId = $zoneId;
        $this->editingRateId = $rateId;

        if ($rateId !== null) {
            $rate = ShippingRate::query()->where('zone_id', $zoneId)->find($rateId);

            if ($rate === null) {
                return;
            }

            $this->rateName = $rate->name;
            $this->rateType = $rate->type->value;
            $this->rateActive = $rate->is_active;
            $this->rateAmount = $rate->config_json['amount'] ?? null;
            $this->rateRanges = array_values($rate->config_json['ranges'] ?? []);
        } else {
            $this->rateName = '';
            $this->rateType = 'flat';
            $this->rateActive = true;
            $this->rateAmount = null;
            $this->rateRanges = [];
        }

        $this->showRateForm = true;
    }

    /**
     * Add an empty range row for weight/price based rates.
     */
    public function addRange(): void
    {
        $this->rateRanges[] = $this->rateType === 'weight'
            ? ['min_g' => null, 'max_g' => null, 'amount' => null]
            : ['min_amount' => null, 'max_amount' => null, 'amount' => null];
    }

    /**
     * Remove a range row.
     */
    public function removeRange(int $index): void
    {
        unset($this->rateRanges[$index]);
        $this->rateRanges = array_values($this->rateRanges);
    }

    /**
     * Reset type-dependent fields when the rate type changes.
     */
    public function updatedRateType(): void
    {
        $this->rateRanges = [];
        $this->rateAmount = null;
    }

    /**
     * Create or update a shipping rate (spec 03 §11.3).
     */
    public function saveRate(): void
    {
        Gate::authorize('manage-shipping');

        $this->normalizeRateInputs();

        $validated = $this->validate($this->rateRules());

        $zone = ShippingZone::query()->find($this->rateZoneId);

        if ($zone === null) {
            return;
        }

        $data = [
            'name' => $validated['rateName'],
            'type' => $validated['rateType'],
            'config_json' => $this->buildRateConfig($validated),
            'is_active' => $this->rateActive,
        ];

        if ($this->editingRateId !== null) {
            $rate = ShippingRate::query()->where('zone_id', $zone->id)->find($this->editingRateId);

            if ($rate === null) {
                return;
            }

            $rate->update($data);
        } else {
            $zone->rates()->create($data);
        }

        $this->showRateForm = false;
        $this->editingRateId = null;

        $this->dispatch('toast', type: 'success', message: 'Shipping rate saved');
    }

    /**
     * Delete a shipping rate.
     */
    public function deleteRate(int $rateId): void
    {
        Gate::authorize('manage-shipping');

        $this->rateInCurrentStore($rateId)?->delete();

        $this->dispatch('toast', type: 'success', message: 'Shipping rate deleted');
    }

    /**
     * Toggle a rate's active flag from the rates table.
     */
    public function toggleRate(int $rateId): void
    {
        Gate::authorize('manage-shipping');

        $rate = $this->rateInCurrentStore($rateId);

        $rate?->update(['is_active' => ! $rate->is_active]);
    }

    public function render(): View
    {
        return view('livewire.admin.settings.shipping', [
            'zones' => ShippingZone::query()->with('rates')->orderBy('id')->get(),
        ])->layout('admin.layouts.app')->title('Shipping');
    }

    /**
     * Look up a rate only when its zone belongs to the current store.
     */
    private function rateInCurrentStore(int $rateId): ?ShippingRate
    {
        return ShippingRate::query()
            ->whereHas('zone', fn ($query) => $query->where('store_id', app('current_store')->getKey()))
            ->find($rateId);
    }

    /**
     * Human-readable summary of a rate's configuration.
     *
     * @param  array<string, mixed>|null  $config
     */
    public function configSummary(ShippingRateType $type, ?array $config): string
    {
        $config ??= [];

        return match ($type) {
            ShippingRateType::Flat => isset($config['amount']) ? number_format($config['amount'] / 100, 2) : '—',
            ShippingRateType::Weight => count($config['ranges'] ?? []).' weight ranges',
            ShippingRateType::Price => count($config['ranges'] ?? []).' price ranges',
            ShippingRateType::Carrier => 'Carrier-calculated',
        };
    }

    /**
     * Convert empty-string inputs to null so integer validation passes.
     */
    private function normalizeRateInputs(): void
    {
        if ($this->rateAmount === '' || $this->rateAmount === null) {
            $this->rateAmount = null;
        } else {
            $this->rateAmount = (int) $this->rateAmount;
        }

        foreach ($this->rateRanges as $index => $range) {
            foreach ($range as $key => $value) {
                $this->rateRanges[$index][$key] = $value === '' || $value === null ? null : (int) $value;
            }
        }
    }

    /**
     * Validation rules for the rate form, conditional on the rate type.
     * Amounts are integers in minor units (spec 05 §9).
     *
     * @return array<string, mixed>
     */
    private function rateRules(): array
    {
        $rules = [
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', Rule::in(['flat', 'weight', 'price', 'carrier'])],
            'rateActive' => ['boolean'],
        ];

        if ($this->rateType === 'flat') {
            $rules['rateAmount'] = ['required', 'integer', 'min:0'];
        }

        if ($this->rateType === 'weight') {
            $rules['rateRanges'] = ['required', 'array', 'min:1'];
            $rules['rateRanges.*.min_g'] = ['required', 'integer', 'min:0'];
            $rules['rateRanges.*.max_g'] = ['required', 'integer', 'gte:rateRanges.*.min_g'];
            $rules['rateRanges.*.amount'] = ['required', 'integer', 'min:0'];
        }

        if ($this->rateType === 'price') {
            $rules['rateRanges'] = ['required', 'array', 'min:1'];
            $rules['rateRanges.*.min_amount'] = ['required', 'integer', 'min:0'];
            $rules['rateRanges.*.max_amount'] = ['nullable', 'integer', 'gte:rateRanges.*.min_amount'];
            $rules['rateRanges.*.amount'] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    /**
     * Build the config_json payload for the rate type (spec 05 §9).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildRateConfig(array $validated): array
    {
        return match ($validated['rateType']) {
            'flat' => ['amount' => (int) $validated['rateAmount']],
            'weight' => [
                'ranges' => array_map(fn (array $range): array => [
                    'min_g' => (int) $range['min_g'],
                    'max_g' => (int) $range['max_g'],
                    'amount' => (int) $range['amount'],
                ], $validated['rateRanges']),
            ],
            'price' => [
                'ranges' => array_map(fn (array $range): array => array_filter([
                    'min_amount' => (int) $range['min_amount'],
                    'max_amount' => isset($range['max_amount']) ? (int) $range['max_amount'] : null,
                    'amount' => (int) $range['amount'],
                ], fn ($value): bool => $value !== null), $validated['rateRanges']),
            ],
            default => [],
        };
    }

    /**
     * Parse a comma-separated list of ISO codes into an uppercase list.
     *
     * @return list<string>
     */
    private function parseCodeList(string $input): array
    {
        return collect(explode(',', $input))
            ->map(fn (string $code): string => mb_strtoupper(trim($code)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
