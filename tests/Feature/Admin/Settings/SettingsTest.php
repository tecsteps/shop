<?php

use App\Livewire\Admin\Settings\Domains;
use App\Livewire\Admin\Settings\General;
use App\Livewire\Admin\Settings\Index;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Taxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

// Settings Index
it('requires authentication for settings', function () {
    $this->get(route('admin.settings.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the settings page with tabs', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

// General Settings
it('loads general settings from the store', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(General::class)
        ->assertSet('storeName', $this->ctx['store']->name)
        ->assertSet('storeHandle', $this->ctx['store']->handle);
});

it('saves general settings', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(General::class)
        ->set('storeName', 'Updated Store')
        ->set('defaultCurrency', 'USD')
        ->call('save')
        ->assertDispatched('toast');

    $this->ctx['store']->refresh();
    expect($this->ctx['store']->name)->toBe('Updated Store');
    expect($this->ctx['store']->default_currency)->toBe('USD');
});

it('validates store name is required', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(General::class)
        ->set('storeName', '')
        ->call('save')
        ->assertHasErrors(['storeName' => 'required']);
});

// Domains
it('lists existing domains', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Domains::class)
        ->assertSee($this->ctx['domain']->hostname);
});

it('adds a new domain', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Domains::class)
        ->set('newHostname', 'shop.test.com')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertDispatched('toast');

    expect(StoreDomain::where('hostname', 'shop.test.com')->exists())->toBeTrue();
});

it('prevents removing primary domain', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Domains::class)
        ->call('removeDomain', $this->ctx['domain']->id)
        ->assertDispatched('toast');

    expect(StoreDomain::find($this->ctx['domain']->id))->not->toBeNull();
});

// Shipping
it('requires authentication for shipping settings', function () {
    $this->get(route('admin.settings.shipping'))
        ->assertRedirect(route('admin.login'));
});

it('renders shipping settings page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.settings.shipping'))
        ->assertOk()
        ->assertSeeLivewire(Shipping::class);
});

it('creates a shipping zone', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Shipping::class)
        ->call('openZoneModal')
        ->set('zoneName', 'Domestic')
        ->set('zoneCountries', ['US', 'CA'])
        ->call('saveZone')
        ->assertDispatched('toast');

    expect(ShippingZone::where('name', 'Domestic')->exists())->toBeTrue();
});

it('creates a shipping rate for a zone', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Test Zone',
        'countries_json' => ['US'],
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Shipping::class)
        ->call('openRateModal', $zone->id)
        ->set('rateName', 'Standard')
        ->set('rateType', 'flat')
        ->set('rateConfig.price', 500)
        ->call('saveRate')
        ->assertDispatched('toast');

    expect(ShippingRate::where('name', 'Standard')->exists())->toBeTrue();
});

it('deletes a shipping zone', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Delete Zone',
        'countries_json' => ['US'],
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Shipping::class)
        ->call('deleteZone', $zone->id)
        ->assertDispatched('toast');

    expect(ShippingZone::find($zone->id))->toBeNull();
});

it('tests shipping address lookup', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'US Zone',
        'countries_json' => ['US'],
    ]);

    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['price' => 500],
        'is_active' => true,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Shipping::class)
        ->set('testCountry', 'US')
        ->call('testShippingAddress')
        ->assertSet('testResult.matched', true)
        ->assertSet('testResult.zone_name', 'US Zone');
});

// Tax Settings
it('requires authentication for tax settings', function () {
    $this->get(route('admin.settings.taxes'))
        ->assertRedirect(route('admin.login'));
});

it('renders tax settings page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.settings.taxes'))
        ->assertOk()
        ->assertSeeLivewire(Taxes::class);
});

it('saves tax settings in manual mode', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Taxes::class)
        ->set('mode', 'manual')
        ->set('manualRates.0.zone_name', 'EU')
        ->set('manualRates.0.rate_percentage', '19')
        ->set('pricesIncludeTax', true)
        ->call('save')
        ->assertDispatched('toast');

    $settings = TaxSettings::where('store_id', $this->ctx['store']->id)->first();
    expect($settings->mode->value)->toBe('manual');
    expect($settings->prices_include_tax)->toBeTrue();
});

it('adds and removes manual tax rates', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Taxes::class)
        ->assertCount('manualRates', 1)
        ->call('addManualRate')
        ->assertCount('manualRates', 2)
        ->call('removeManualRate', 0)
        ->assertCount('manualRates', 1);
});
