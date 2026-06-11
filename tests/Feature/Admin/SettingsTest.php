<?php

use App\Enums\StoreUserRole;
use App\Enums\TaxMode;
use App\Livewire\Admin\Settings\Domains as DomainsSettings;
use App\Livewire\Admin\Settings\General as GeneralSettings;
use App\Livewire\Admin\Settings\Shipping as ShippingSettings;
use App\Livewire\Admin\Settings\Taxes as TaxesSettings;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the settings page', function () {
    actingAsAdmin($this->user)
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Domains')
        ->assertSee('Shipping')
        ->assertSee('Taxes')
        ->assertSee('Checkout')
        ->assertSee('Notifications');
});

it('updates general store settings', function () {
    actingAsAdmin($this->user);

    Livewire::test(GeneralSettings::class)
        ->set('storeName', 'Renamed Store')
        ->set('contactEmail', 'support@renamed.test')
        ->set('orderNumberPrefix', 'RS-')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($this->store->refresh()->name)->toBe('Renamed Store');

    $settings = $this->store->settings()->first()->settings_json;

    expect($settings['store_name'])->toBe('Renamed Store');
    expect($settings['contact_email'])->toBe('support@renamed.test');
    expect($settings['order_number_prefix'])->toBe('RS-');
});

it('configures shipping zones', function () {
    actingAsAdmin($this->user);

    $component = Livewire::test(ShippingSettings::class)
        ->call('openZoneModal')
        ->set('zoneName', 'Domestic')
        ->set('zoneCountries', ['DE', 'AT'])
        ->call('saveZone')
        ->assertHasNoErrors();

    $zone = ShippingZone::query()->where('name', 'Domestic')->firstOrFail();

    expect($zone->countries_json)->toBe(['DE', 'AT']);
    expect($zone->store_id)->toBe($this->store->getKey());

    $component
        ->call('openRateModal', $zone->getKey())
        ->set('rateName', 'Standard')
        ->set('rateType', 'flat')
        ->set('rateFlatAmount', '5.00')
        ->call('saveRate')
        ->assertHasNoErrors();

    $rate = ShippingRate::query()->where('name', 'Standard')->firstOrFail();

    expect($rate->zone_id)->toBe($zone->getKey());
    expect($rate->config_json['amount'])->toBe(500);
    expect($rate->is_active)->toBeTrue();
});

it('configures tax settings', function () {
    actingAsAdmin($this->user);

    Livewire::test(TaxesSettings::class)
        ->set('mode', 'manual')
        ->set('manualRate', '19.00')
        ->set('pricesIncludeTax', true)
        ->set('shippingTaxable', false)
        ->call('save')
        ->assertHasNoErrors();

    $settings = TaxSettings::query()->findOrFail($this->store->getKey());

    expect($settings->mode)->toBe(TaxMode::Manual);
    expect($settings->defaultRateBasisPoints())->toBe(1900);
    expect($settings->prices_include_tax)->toBeTrue();
    expect($settings->shippingTaxable())->toBeFalse();
});

it('manages store domains', function () {
    actingAsAdmin($this->user);

    Livewire::test(DomainsSettings::class)
        ->set('newHostname', 'shop.example.com')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('store_domains', [
        'store_id' => $this->store->getKey(),
        'hostname' => 'shop.example.com',
        'type' => 'storefront',
    ]);
});

it('restricts settings to owner and admin roles', function () {
    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/settings')
        ->assertForbidden();

    actingAsAdmin($staff, $this->store)
        ->get('/admin/settings/shipping')
        ->assertForbidden();

    actingAsAdmin($staff, $this->store)
        ->get('/admin/settings/taxes')
        ->assertForbidden();
});
