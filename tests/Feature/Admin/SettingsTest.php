<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the general settings page', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.settings.index'))
        ->assertOk();
});

it('updates store name via general settings', function () {
    Livewire::actingAs($this->user)
        ->test(SettingsIndex::class)
        ->set('storeName', 'Updated Store Name')
        ->call('save');

    expect($this->store->fresh()->name)->toBe('Updated Store Name');
});

it('renders the shipping settings page', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.settings.shipping'))
        ->assertOk();
});

it('creates a shipping zone', function () {
    Livewire::actingAs($this->user)
        ->test(SettingsShipping::class)
        ->call('openCreateForm')
        ->set('zoneName', 'Europe')
        ->set('countries', 'DE, AT, CH')
        ->call('save');

    expect(ShippingZone::query()->withoutGlobalScopes()->where('name', 'Europe')->exists())
        ->toBeTrue();
});

it('renders the tax settings page', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.settings.taxes'))
        ->assertOk();
});

it('saves tax settings', function () {
    Livewire::actingAs($this->user)
        ->test(SettingsTaxes::class)
        ->set('taxRate', 2000)
        ->set('pricesIncludeTax', true)
        ->call('save');

    $settings = TaxSettings::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($settings->rate_percent)->toBe(2000)
        ->and($settings->prices_include_tax)->toBeTrue();
});
