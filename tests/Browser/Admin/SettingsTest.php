<?php

use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('can view store settings', function () {
    visit('/admin/settings')
        ->assertSee('Settings')
        ->assertSee('Acme Fashion')
        ->assertValue('storeName', 'Acme Fashion')
        ->assertNoJavascriptErrors();
});

test('can update store name', function () {
    visit('/admin/settings')
        ->fill('storeName', 'Acme Fashion Updated')
        ->press('Save')
        ->wait(1)
        ->assertSee('Settings saved')
        ->assertNoJavascriptErrors();

    // Reload the page and verify the change persisted.
    visit('/admin/settings')
        ->assertSee('Acme Fashion Updated')
        ->assertValue('storeName', 'Acme Fashion Updated')
        ->assertNoJavascriptErrors();

    expect(Store::query()->where('handle', 'acme-fashion')->sole()->name)->toBe('Acme Fashion Updated');
});

test('can view shipping zones', function () {
    visit('/admin/settings')
        ->click('div[role="tablist"] a:has-text("Shipping")')
        ->wait(1)
        ->assertSee('Domestic')
        ->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->assertNoJavascriptErrors();
});

test('can add a new shipping rate to existing zone', function () {
    $zone = ShippingZone::query()->where('name', 'Domestic')->sole();

    // Target the "Add rate" button inside the Domestic zone card exactly.
    // The rate amount field is in cents (minor units): 1499 = 14.99 EUR.
    visit('/admin/settings/shipping')
        ->press('[wire\:click="openRateForm('.$zone->id.')"]')
        ->wait(1)
        ->fill('rateName', 'Overnight Shipping')
        ->fill('rateAmount', '1499')
        ->press('Save rate')
        ->wait(1)
        ->assertSee('Shipping rate saved')
        ->assertSee('Overnight Shipping')
        ->assertSee('14.99')
        ->assertNoJavascriptErrors();

    expect(
        $zone->rates()->where('name', 'Overnight Shipping')->exists()
    )->toBeTrue();
});

test('can view tax settings', function () {
    visit('/admin/settings')
        ->click('div[role="tablist"] a:has-text("Taxes")')
        ->wait(1)
        ->assertSee('Taxes')
        ->assertSee('Tax mode')
        ->assertSee('Rates')
        ->assertNoJavascriptErrors();
});

test('can update tax inclusion setting', function () {
    $store = Store::query()->where('handle', 'acme-fashion')->sole();

    // Seeded with prices_include_tax = true; the toggle flips it to false.
    visit('/admin/settings/taxes')
        ->press('ui-switch[data-flux-switch]')
        ->press('Save')
        ->wait(1)
        ->assertSee('Settings saved')
        ->assertNoJavascriptErrors();

    expect(TaxSettings::query()->find($store->id)->prices_include_tax)->toBeFalse();
});

test('can view domain settings', function () {
    visit('/admin/settings')
        ->press('button[role="tab"]:has-text("Domains")')
        ->wait(1)
        ->assertSee('acme-fashion.test')
        ->assertNoJavascriptErrors();
});
