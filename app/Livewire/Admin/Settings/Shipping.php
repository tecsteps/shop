<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Shipping extends AdminComponent
{
    public ?ShippingZone $editingZone = null;

    public string $zoneName = '';

    /** @var list<string> */
    public array $zoneCountries = [];

    public ?ShippingRate $editingRate = null;

    public ?int $rateZoneId = null;

    public string $rateName = '';

    public string $rateType = 'flat';

    /** @var array<string, mixed> */
    public array $rateConfig = [];

    public string $rateAmount = '0.00';

    public bool $rateActive = true;

    /** @var array<string, string> */
    public array $testAddress = ['country' => 'DE', 'province' => '', 'city' => '', 'postal_code' => ''];

    /** @var array<string, mixed>|null */
    public ?array $testResult = null;

    public function mount(): void
    {
        $this->authorizeSettings();
    }

    public function openZoneModal(?int $zoneId = null): void
    {
        $this->authorizeSettings();
        $this->editingZone = $zoneId ? $this->zone($zoneId) : null;
        $this->zoneName = (string) ($this->editingZone?->name ?? '');
        $this->zoneCountries = array_values((array) ($this->editingZone?->countries_json ?? []));
        $this->resetValidation();
        $this->dispatch('modal-show', name: 'zone-form');
    }

    public function saveZone(): void
    {
        $this->authorizeSettings();
        $data = $this->validate(['zoneName' => ['required', 'string', 'max:255'], 'zoneCountries' => ['required', 'array', 'min:1'], 'zoneCountries.*' => ['string', 'size:2']]);
        ShippingZone::withoutGlobalScopes()->updateOrCreate(
            ['id' => $this->editingZone?->id, 'store_id' => $this->currentStore()->id],
            ['name' => trim($data['zoneName']), 'countries_json' => array_values(array_unique(array_map('strtoupper', $data['zoneCountries']))), 'regions_json' => (array) ($this->editingZone?->regions_json ?? [])],
        );
        $this->editingZone = null;
        unset($this->zones);
        $this->dispatch('modal-close', name: 'zone-form');
        $this->toast('Shipping zone saved');
    }

    public function deleteZone(int $zoneId): void
    {
        $this->authorizeSettings();
        $this->zone($zoneId)->delete();
        unset($this->zones);
        $this->toast('Shipping zone deleted');
    }

    public function openRateModal(int $zoneId, ?int $rateId = null): void
    {
        $this->authorizeSettings();
        $this->zone($zoneId);
        $this->editingRate = $rateId ? $this->rate($rateId, $zoneId) : null;
        $this->rateZoneId = $zoneId;
        $this->rateName = (string) ($this->editingRate?->name ?? '');
        $this->rateType = (string) $this->enumValue($this->editingRate?->type ?? 'flat');
        $this->rateConfig = (array) ($this->editingRate?->config_json ?? []);
        $amount = (int) data_get($this->rateConfig, 'amount', data_get($this->rateConfig, 'ranges.0.amount', data_get($this->rateConfig, 'fallback_amount', 0)));
        $this->rateAmount = number_format($amount / 100, 2, '.', '');
        $this->rateActive = (bool) ($this->editingRate?->is_active ?? true);
        $this->resetValidation();
        $this->dispatch('modal-show', name: 'rate-form');
    }

    public function saveRate(): void
    {
        $this->authorizeSettings();
        abort_unless($this->rateZoneId, 422);
        $this->zone($this->rateZoneId);
        $data = $this->validate([
            'rateName' => ['required', 'string', 'max:255'],
            'rateType' => ['required', Rule::in(['flat', 'weight', 'price', 'carrier'])],
            'rateAmount' => ['required_unless:rateType,carrier', 'nullable', 'numeric', 'min:0', 'max:1000000'],
            'rateActive' => ['boolean'],
            'rateConfig.min_g' => ['nullable', 'integer', 'min:0'], 'rateConfig.max_g' => ['nullable', 'integer', 'gte:rateConfig.min_g'],
            'rateConfig.min_amount' => ['nullable', 'numeric', 'min:0'], 'rateConfig.max_amount' => ['nullable', 'numeric', 'gte:rateConfig.min_amount'],
        ]);
        $amount = (int) round((float) ($data['rateAmount'] ?? 0) * 100);
        $config = match ($data['rateType']) {
            'weight' => ['ranges' => [['min_g' => (int) data_get($data, 'rateConfig.min_g', 0), 'max_g' => filled(data_get($data, 'rateConfig.max_g')) ? (int) data_get($data, 'rateConfig.max_g') : null, 'amount' => $amount]]],
            'price' => ['ranges' => [['min_amount' => (int) round((float) data_get($data, 'rateConfig.min_amount', 0) * 100), 'max_amount' => filled(data_get($data, 'rateConfig.max_amount')) ? (int) round((float) data_get($data, 'rateConfig.max_amount') * 100) : null, 'amount' => $amount]]],
            'carrier' => ['carrier' => 'mock', 'service' => 'standard', 'fallback_amount' => 999],
            default => ['amount' => $amount],
        };
        ShippingRate::query()->updateOrCreate(['id' => $this->editingRate?->id, 'zone_id' => $this->rateZoneId], ['name' => trim($data['rateName']), 'type' => $data['rateType'], 'config_json' => $config, 'is_active' => (bool) $data['rateActive']]);
        $this->editingRate = null;
        unset($this->zones);
        $this->dispatch('modal-close', name: 'rate-form');
        $this->toast('Shipping rate saved');
    }

    public function deleteRate(int $rateId): void
    {
        $this->authorizeSettings();
        $rate = ShippingRate::query()->whereHas('zone', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $this->currentStore()->id))->findOrFail($rateId);
        $rate->delete();
        unset($this->zones);
        $this->toast('Shipping rate deleted');
    }

    public function testShippingAddress(): void
    {
        $this->authorizeSettings();
        $data = $this->validate(['testAddress.country' => ['required', 'string', 'size:2'], 'testAddress.province' => ['nullable', 'string', 'max:100'], 'testAddress.city' => ['nullable', 'string', 'max:100'], 'testAddress.postal_code' => ['nullable', 'string', 'max:20']]);
        $calculator = app(ShippingCalculator::class);
        $address = ['country_code' => strtoupper($data['testAddress']['country']), 'province_code' => strtoupper($data['testAddress']['province'] ?? ''), 'city' => $data['testAddress']['city'] ?? '', 'postal_code' => $data['testAddress']['postal_code'] ?? ''];
        $zone = $calculator->matchingZone($this->currentStore(), $address);
        $this->testResult = $zone ? ['zone' => $zone->name, 'rates' => $zone->rates()->where('is_active', true)->get()->map(fn (ShippingRate $rate): array => ['name' => $rate->name, 'amount' => (int) data_get($rate->config_json, 'amount', data_get($rate->config_json, 'ranges.0.amount', data_get($rate->config_json, 'fallback_amount', 0)))])->all()] : ['zone' => null, 'rates' => []];
    }

    #[Computed]
    public function zones(): mixed
    {
        return ShippingZone::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->with('rates')->orderBy('name')->get();
    }

    #[Computed]
    public function countries(): array
    {
        return ['DE' => 'Germany', 'AT' => 'Austria', 'BE' => 'Belgium', 'CH' => 'Switzerland', 'DK' => 'Denmark', 'ES' => 'Spain', 'FR' => 'France', 'GB' => 'United Kingdom', 'IT' => 'Italy', 'NL' => 'Netherlands', 'PL' => 'Poland', 'PT' => 'Portugal', 'US' => 'United States', 'CA' => 'Canada', 'AU' => 'Australia'];
    }

    public function render(): View
    {
        return $this->admin(view('admin.settings.shipping'), 'Shipping', [['label' => 'Settings', 'url' => url('/admin/settings')], ['label' => 'Shipping']]);
    }

    private function authorizeSettings(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }

    private function zone(int $id): ShippingZone
    {
        return ShippingZone::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->findOrFail($id);
    }

    private function rate(int $id, int $zoneId): ShippingRate
    {
        return ShippingRate::query()->where('zone_id', $zoneId)->findOrFail($id);
    }
}
