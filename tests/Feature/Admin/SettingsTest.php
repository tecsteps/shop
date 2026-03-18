<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access settings', function () {
    auth()->logout();
    $this->get('/admin/settings')->assertRedirect('/admin/login');
});

it('renders the general settings page', function () {
    $this->get('/admin/settings')
        ->assertStatus(200)
        ->assertSee('Settings');
});

it('updates general store settings', function () {
    $component = Livewire::test(SettingsIndex::class);

    $component->set('storeName', 'Updated Store');
    $component->set('defaultCurrency', 'USD');
    $component->set('timezone', 'America/New_York');
    $component->call('save');

    $this->ctx['store']->refresh();
    expect($this->ctx['store']->name)->toBe('Updated Store');
    expect($this->ctx['store']->default_currency)->toBe('USD');
});

it('validates store name is required', function () {
    $component = Livewire::test(SettingsIndex::class);
    $component->set('storeName', '');
    $component->call('save');
    $component->assertHasErrors('storeName');
});

it('renders the shipping settings page', function () {
    $this->get('/admin/settings/shipping')
        ->assertStatus(200)
        ->assertSee('Shipping');
});

it('creates a shipping zone', function () {
    $component = Livewire::test(SettingsShipping::class);

    $component->set('newZoneName', 'Domestic');
    $component->set('newZoneCountries', 'DE, AT');
    $component->call('createZone');

    $zone = ShippingZone::where('store_id', $this->ctx['store']->id)->first();
    expect($zone)->not->toBeNull();
    expect($zone->name)->toBe('Domestic');
    expect($zone->countries_json)->toBe(['DE', 'AT']);
});

it('validates zone name is required', function () {
    $component = Livewire::test(SettingsShipping::class);
    $component->set('newZoneName', '');
    $component->call('createZone');
    $component->assertHasErrors('newZoneName');
});

it('deletes a shipping zone', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'To Delete',
        'countries_json' => ['US'],
    ]);

    $component = Livewire::test(SettingsShipping::class);
    $component->call('deleteZone', $zone->id);

    expect(ShippingZone::find($zone->id))->toBeNull();
});

it('adds a shipping rate to a zone', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Test Zone',
        'countries_json' => ['US'],
    ]);

    $component = Livewire::test(SettingsShipping::class);
    $component->set('selectedZoneId', $zone->id);
    $component->set('newRateName', 'Standard');
    $component->set('newRatePrice', 499);
    $component->call('addRate');

    $rate = ShippingRate::where('zone_id', $zone->id)->first();
    expect($rate)->not->toBeNull();
    expect($rate->name)->toBe('Standard');
    expect($rate->config_json['price'])->toBe(499);
});

it('deletes a shipping rate', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Test Zone',
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'To Delete',
        'type' => 'flat',
        'config_json' => ['price' => 500],
        'is_active' => true,
    ]);

    $component = Livewire::test(SettingsShipping::class);
    $component->call('deleteRate', $rate->id);

    expect(ShippingRate::find($rate->id))->toBeNull();
});

it('renders the tax settings page', function () {
    $this->get('/admin/settings/taxes')
        ->assertStatus(200)
        ->assertSee('Tax');
});

it('saves tax settings', function () {
    $component = Livewire::test(SettingsTaxes::class);

    $component->set('mode', 'manual');
    $component->set('pricesIncludeTax', true);
    $component->set('defaultRate', 19.0);
    $component->call('save');

    $settings = TaxSettings::where('store_id', $this->ctx['store']->id)->first();
    expect($settings)->not->toBeNull();
    expect($settings->mode->value)->toBe('manual');
    expect($settings->prices_include_tax)->toBeTrue();
    expect((float) $settings->config_json['default_rate'])->toBe(19.0);
});

it('updates existing tax settings', function () {
    TaxSettings::create([
        'store_id' => $this->ctx['store']->id,
        'mode' => 'manual',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 10.0],
    ]);

    $component = Livewire::test(SettingsTaxes::class);
    $component->set('defaultRate', 21.0);
    $component->set('pricesIncludeTax', true);
    $component->call('save');

    $settings = TaxSettings::where('store_id', $this->ctx['store']->id)->first();
    expect((float) $settings->config_json['default_rate'])->toBe(21.0);
    expect($settings->prices_include_tax)->toBeTrue();
});

it('validates tax rate is within bounds', function () {
    $component = Livewire::test(SettingsTaxes::class);
    $component->set('defaultRate', 150);
    $component->call('save');
    $component->assertHasErrors('defaultRate');
});
