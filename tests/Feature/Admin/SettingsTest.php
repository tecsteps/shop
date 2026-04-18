<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use Livewire\Livewire;

it('updates general store settings', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'st-general.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(SettingsIndex::class)
        ->set('name', 'Brand New Name')
        ->set('default_currency', 'EUR')
        ->set('timezone', 'Europe/Berlin')
        ->set('default_locale', 'de')
        ->call('saveGeneral');

    $fresh = $ctx['store']->fresh();
    expect($fresh->name)->toBe('Brand New Name');
    expect($fresh->default_currency)->toBe('EUR');
    expect($fresh->timezone)->toBe('Europe/Berlin');
});

it('adds and removes store domains', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'st-dom.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(SettingsIndex::class)
        ->set('tab', 'domains')
        ->set('newDomain', 'extra.example.test')
        ->call('addDomain');

    $domain = StoreDomain::query()->where('hostname', 'extra.example.test')->first();
    expect($domain)->not->toBeNull();

    Livewire::test(SettingsIndex::class)
        ->call('removeDomain', $domain->id);

    expect(StoreDomain::query()->find($domain->id))->toBeNull();
});

it('creates a shipping zone and rate', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'st-ship.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(SettingsShipping::class)
        ->set('newZoneName', 'US Zone')
        ->set('newZoneCountries', 'US,CA')
        ->call('addZone');

    $zone = ShippingZone::query()->where('name', 'US Zone')->first();
    expect($zone)->not->toBeNull();
    expect($zone->countries_json)->toBe(['US', 'CA']);

    Livewire::test(SettingsShipping::class)
        ->set("newRate.{$zone->id}.name", 'Standard')
        ->set("newRate.{$zone->id}.type", 'flat')
        ->set("newRate.{$zone->id}.amount", 799)
        ->call('addRate', $zone->id);

    $rate = ShippingRate::query()->where('zone_id', $zone->id)->first();
    expect($rate)->not->toBeNull();
    expect($rate->config_json['amount'])->toBe(799);
});

it('saves tax settings', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'st-tax.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(SettingsTaxes::class)
        ->set('mode', 'manual')
        ->set('prices_include_tax', true)
        ->set('default_rate_bp', 2000)
        ->call('save');

    $row = TaxSettings::query()->where('store_id', $ctx['store']->id)->first();
    expect($row)->not->toBeNull();
    expect($row->prices_include_tax)->toBeTrue();
    expect($row->config_json['default_rate_bp'])->toBe(2000);
});
