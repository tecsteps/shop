<?php

use App\Livewire\Admin\Settings\Domains;
use App\Livewire\Admin\Settings\General;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Taxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('renders the settings page', function () {
    $this->get(route('admin.settings.index'))->assertOk();
});

it('saves general settings', function () {
    Livewire::test(General::class)
        ->set('storeName', 'Renamed Store')
        ->set('contactEmail', 'hello@example.com')
        ->call('save')
        ->assertDispatched('toast');

    expect($this->store->refresh()->name)->toBe('Renamed Store')
        ->and($this->store->storeSetting->settings_json['contact_email'])->toBe('hello@example.com');
});

it('adds and removes domains', function () {
    Livewire::test(Domains::class)
        ->set('newHostname', 'shop.example.com')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertDispatched('toast');

    $domain = StoreDomain::query()->where('hostname', 'shop.example.com')->firstOrFail();

    Livewire::test(Domains::class)
        ->call('setPrimary', $domain->getKey())
        ->assertDispatched('toast');

    expect($domain->refresh()->is_primary)->toBeTrue();

    $oldPrimary = StoreDomain::query()
        ->where('store_id', $this->store->getKey())
        ->whereKeyNot($domain->getKey())
        ->firstOrFail();

    Livewire::test(Domains::class)->call('removeDomain', $oldPrimary->getKey());

    expect(StoreDomain::query()->whereKey($oldPrimary->getKey())->exists())->toBeFalse();
});

it('refuses to remove the primary domain', function () {
    $primary = $this->store->storeDomains()->firstOrFail();
    $primary->update(['is_primary' => true]);

    Livewire::test(Domains::class)
        ->call('removeDomain', $primary->getKey())
        ->assertDispatched('toast');

    expect(StoreDomain::query()->whereKey($primary->getKey())->exists())->toBeTrue();
});

it('creates shipping zones and rates', function () {
    Livewire::test(Shipping::class)
        ->call('openZoneModal')
        ->set('zoneName', 'Domestic')
        ->set('zoneCountries', ['DE'])
        ->call('saveZone')
        ->assertDispatched('toast');

    $zone = ShippingZone::query()->where('store_id', $this->store->getKey())->where('name', 'Domestic')->firstOrFail();

    Livewire::test(Shipping::class)
        ->call('openRateModal', $zone->getKey())
        ->set('rateName', 'Standard')
        ->set('rateType', 'flat')
        ->set('ratePrice', '4.99')
        ->call('saveRate')
        ->assertDispatched('toast');

    expect(ShippingRate::query()->where('zone_id', $zone->getKey())->firstOrFail()->config_json['amount'])->toBe(499);
});

it('deletes shipping zones', function () {
    $rate = CommerceTestHelper::flatShippingRate($this->store);
    $zoneId = $rate->zone_id;

    Livewire::test(Shipping::class)
        ->call('deleteZone', $zoneId)
        ->assertDispatched('toast');

    expect(ShippingZone::query()->whereKey($zoneId)->exists())->toBeFalse();
});

it('tests a shipping address against zones', function () {
    CommerceTestHelper::flatShippingRate($this->store, ['DE'], 499);

    Livewire::test(Shipping::class)
        ->set('testCountry', 'DE')
        ->call('testShippingAddress')
        ->assertSet('testResult.matched', true);
});

it('saves tax settings with manual rates', function () {
    Livewire::test(Taxes::class)
        ->call('addManualRate')
        ->set('manualRates.0.zone_name', 'DE')
        ->set('manualRates.0.rate_percentage', '19.00')
        ->call('save')
        ->assertDispatched('toast');

    $settings = $this->store->refresh()->taxSetting;

    expect($settings->config_json['tax_rates'][0]['rate'])->toBe(1900);
});
