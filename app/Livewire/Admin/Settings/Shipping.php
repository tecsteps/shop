<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Shipping extends Component
{
    public bool $showZoneForm = false;

    public ?int $editingZoneId = null;

    #[Validate('required|string|max:255')]
    public string $zoneName = '';

    #[Validate('required|string')]
    public string $countries = '';

    public function openCreateForm(): void
    {
        $this->zoneName = '';
        $this->countries = '';
        $this->editingZoneId = null;
        $this->showZoneForm = true;
    }

    public function openEditForm(int $zoneId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $zone = ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($zoneId);

        $this->editingZoneId = $zone->id;
        $this->zoneName = $zone->name;
        $this->countries = implode(', ', $zone->countries_json ?? []);
        $this->showZoneForm = true;
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $countriesArray = array_map('trim', explode(',', $this->countries));

        $data = [
            'name' => $this->zoneName,
            'countries_json' => $countriesArray,
        ];

        if ($this->editingZoneId) {
            ShippingZone::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('id', $this->editingZoneId)
                ->update($data);
        } else {
            ShippingZone::query()->withoutGlobalScopes()->create(array_merge($data, [
                'store_id' => $store->id,
            ]));
        }

        $this->showZoneForm = false;
        $this->dispatch('toast', type: 'success', message: 'Shipping zone saved successfully.');
    }

    public function deleteZone(int $zoneId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('id', $zoneId)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Shipping zone deleted.');
    }

    public function cancel(): void
    {
        $this->showZoneForm = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $zones = ShippingZone::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('rates')
            ->get();

        return view('livewire.admin.settings.shipping', [
            'zones' => $zones,
        ]);
    }
}
