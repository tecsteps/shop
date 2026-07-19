<?php

use App\Livewire\Admin\Settings\Index;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Taxes;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('renders the settings page', function () {
    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Domains')
        ->assertSee('Checkout')
        ->assertSee('Notifications')
        ->assertSee('Shipping')
        ->assertSee('Taxes');
});

test('updates general store settings', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('storeName', 'My New Shop')
        ->set('contactEmail', 'hello@example.com')
        ->set('defaultCurrency', 'USD')
        ->set('timezone', 'Europe/Berlin')
        ->call('saveGeneral')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $this->store->refresh();

    expect($this->store->name)->toBe('My New Shop')
        ->and($this->store->default_currency)->toBe('USD')
        ->and($this->store->timezone)->toBe('Europe/Berlin')
        ->and($this->store->settings->settings_json['contact_email'])->toBe('hello@example.com')
        ->and($this->store->settings->settings_json['store_name'])->toBe('My New Shop');
});

test('manages store domains', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('newHostname', 'shop.example.com')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $domain = StoreDomain::query()->where('hostname', 'shop.example.com')->sole();

    expect($domain->store_id)->toBe($this->store->id)
        ->and($domain->is_primary)->toBeFalse();
});

test('validates hostname format and uniqueness when adding a domain', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('newHostname', 'not a hostname')
        ->call('addDomain')
        ->assertHasErrors(['newHostname']);

    $existing = $this->store->domains()->first();

    Livewire::test(Index::class)
        ->set('newHostname', $existing->hostname)
        ->call('addDomain')
        ->assertHasErrors(['newHostname']);
});

test('sets a domain as primary and demotes the others', function () {
    $oldPrimary = $this->store->domains()->where('is_primary', true)->sole();
    $newPrimary = StoreDomain::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('setPrimary', $newPrimary->id)
        ->assertDispatched('toast');

    expect($newPrimary->refresh()->is_primary)->toBeTrue()
        ->and($oldPrimary->refresh()->is_primary)->toBeFalse();
});

test('cannot remove the primary domain', function () {
    $primary = $this->store->domains()->where('is_primary', true)->sole();

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('removeDomain', $primary->id)
        ->assertDispatched('toast', type: 'error');

    $this->assertDatabaseHas('store_domains', ['id' => $primary->id]);

    $secondary = StoreDomain::factory()->create(['store_id' => $this->store->id]);

    Livewire::test(Index::class)
        ->call('removeDomain', $secondary->id)
        ->assertDispatched('toast', type: 'success');

    $this->assertDatabaseMissing('store_domains', ['id' => $secondary->id]);
});

test('saves checkout settings', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('orderNumberPrefix', 'SHOP-')
        ->set('orderNumberStart', 5000)
        ->set('bankTransferCancelDays', 10)
        ->set('cartAbandonDays', 30)
        ->call('saveCheckout')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = $this->store->settings()->sole()->settings_json;

    expect($settings['order_number_prefix'])->toBe('SHOP-')
        ->and($settings['order_number_start'])->toBe(5000)
        ->and($settings['bank_transfer_cancel_days'])->toBe(10)
        ->and($settings['cart_abandon_days'])->toBe(30);
});

test('configures shipping zones and rates', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Shipping::class)
        ->set('zoneName', 'Europe')
        ->set('zoneCountries', 'DE, FR')
        ->set('zoneRegions', 'BY, BAV')
        ->call('saveZone')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $zone = ShippingZone::query()->where('name', 'Europe')->sole();

    expect($zone->countries_json)->toBe(['DE', 'FR'])
        ->and($zone->regions_json)->toBe(['BY', 'BAV']);

    Livewire::test(Shipping::class)
        ->call('openRateForm', $zone->id)
        ->set('rateName', 'Standard')
        ->set('rateType', 'flat')
        ->set('rateAmount', 500)
        ->call('saveRate')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $this->assertDatabaseHas('shipping_rates', [
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => 'flat',
        'is_active' => true,
    ]);

    expect($zone->rates()->sole()->config_json)->toBe(['amount' => 500]);
});

test('saves weight-based rate ranges', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Shipping::class)
        ->call('openRateForm', $zone->id)
        ->set('rateName', 'Heavy')
        ->set('rateType', 'weight')
        ->set('rateRanges', [
            ['min_g' => 0, 'max_g' => 1000, 'amount' => 500],
            ['min_g' => 1001, 'max_g' => 5000, 'amount' => 900],
        ])
        ->call('saveRate')
        ->assertHasNoErrors();

    $rate = $zone->rates()->sole();

    expect($rate->type->value)->toBe('weight')
        ->and($rate->config_json['ranges'])->toBe([
            ['min_g' => 0, 'max_g' => 1000, 'amount' => 500],
            ['min_g' => 1001, 'max_g' => 5000, 'amount' => 900],
        ]);
});

test('deletes a shipping zone with its rates', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $zone->rates()->create(['name' => 'Standard', 'type' => 'flat', 'config_json' => ['amount' => 500], 'is_active' => true]);

    Livewire::actingAs($this->user);
    Livewire::test(Shipping::class)
        ->call('deleteZone', $zone->id)
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('shipping_zones', ['id' => $zone->id]);
    $this->assertDatabaseMissing('shipping_rates', ['zone_id' => $zone->id]);
});

test('configures tax settings', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Taxes::class)
        ->set('mode', 'manual')
        ->set('defaultRateBps', 1900)
        ->set('pricesIncludeTax', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = TaxSettings::query()->find($this->store->id);

    expect($settings)->not->toBeNull()
        ->and($settings->mode->value)->toBe('manual')
        ->and($settings->prices_include_tax)->toBeTrue()
        ->and($settings->config_json['default_rate_bps'])->toBe(1900);
});

test('saves zone rate overrides and provider fallback', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Taxes::class)
        ->set('mode', 'provider')
        ->set('provider', 'stripe')
        ->set('fallback', 'allow')
        ->set('defaultRateBps', 1900)
        ->set('zoneRates', [$zone->id => 700])
        ->call('save')
        ->assertHasNoErrors();

    $settings = TaxSettings::query()->find($this->store->id);

    expect($settings->mode->value)->toBe('provider')
        ->and($settings->provider)->toBe('stripe')
        ->and($settings->config_json['zone_rates'][$zone->id])->toBe(700)
        ->and($settings->config_json['fallback'])->toBe('allow');
});

test('staff is forbidden from settings, shipping, and taxes', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    foreach (['/admin/settings', '/admin/settings/shipping', '/admin/settings/taxes'] as $url) {
        $this->actingAs($staff)
            ->withSession(['current_store_id' => $this->store->id])
            ->get($url)
            ->assertForbidden();
    }
});
