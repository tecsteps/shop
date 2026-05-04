<?php

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminSettingsBrowserHost(): array
{
    return ['host' => 'shop.test'];
}

function adminSettingsBrowserStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminSettingsBrowserAuthenticate(mixed $testCase): Store
{
    $store = adminSettingsBrowserStore();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);

    return $store;
}

function adminSettingsOpen(mixed $testCase): mixed
{
    adminSettingsBrowserAuthenticate($testCase);

    return visit('/admin/settings', adminSettingsBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin/settings')
        ->assertSee('Store Settings')
        ->assertNoJavaScriptErrors();
}

function adminSettingsOpenShipping(mixed $testCase): mixed
{
    return adminSettingsOpen($testCase)
        ->click('a[href$="/admin/settings/shipping"]')
        ->wait(1)
        ->assertPathIs('/admin/settings/shipping')
        ->assertSee('Shipping')
        ->assertNoJavaScriptErrors();
}

function adminSettingsOpenTaxes(mixed $testCase): mixed
{
    return adminSettingsOpen($testCase)
        ->click('a[href$="/admin/settings/taxes"]')
        ->wait(1)
        ->assertPathIs('/admin/settings/taxes')
        ->assertSee('Tax Settings')
        ->assertNoJavaScriptErrors();
}

test('can view store settings', function (): void {
    adminSettingsOpen($this)
        ->assertValue('input[wire\\:model="storeName"]', 'Acme Fashion')
        ->assertNoJavaScriptErrors();
});

test('can update store name', function (): void {
    $store = adminSettingsBrowserAuthenticate($this);

    $page = adminSettingsOpen($this)
        ->fill('input[wire\\:model="storeName"]', 'Acme Fashion Updated')
        ->click('button[data-test="settings-save-button"]')
        ->wait(1)
        ->assertSee('Settings saved')
        ->assertNoJavaScriptErrors();

    expect($store->refresh()->name)->toBe('Acme Fashion Updated');

    $page
        ->navigate('/admin/settings')
        ->wait(1)
        ->assertValue('input[wire\\:model="storeName"]', 'Acme Fashion Updated')
        ->assertNoJavaScriptErrors();
});

test('can view shipping zones', function (): void {
    adminSettingsOpenShipping($this)
        ->assertSee('Domestic')
        ->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->assertNoJavaScriptErrors();
});

test('can add a new shipping rate to an existing zone', function (): void {
    $store = adminSettingsBrowserAuthenticate($this);

    adminSettingsOpenShipping($this)
        ->click('button[data-test="add-rate-domestic"]')
        ->wait(1)
        ->assertSee('Add rate')
        ->fill('input[wire\\:model="rateName"]', 'Overnight Shipping')
        ->fill('input[wire\\:model="rateAmount"]', '14.99')
        ->click('button[data-test="shipping-rate-save-button"]')
        ->wait(1)
        ->assertSee('Shipping rate saved')
        ->assertSee('Overnight Shipping')
        ->assertSee('14.99')
        ->assertNoJavaScriptErrors();

    $domestic = ShippingZone::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('name', 'Domestic')
        ->firstOrFail();

    $rate = ShippingRate::withoutGlobalScopes()
        ->where('zone_id', $domestic->getKey())
        ->where('name', 'Overnight Shipping')
        ->firstOrFail();

    expect(data_get($rate->config_json, 'amount'))->toBe(1499);
});

test('can view tax settings', function (): void {
    adminSettingsOpenTaxes($this)
        ->assertSee('Manual rates')
        ->assertNoJavaScriptErrors();
});

test('can update tax inclusion setting', function (): void {
    $store = adminSettingsBrowserAuthenticate($this);

    adminSettingsOpenTaxes($this)
        ->click('Prices include tax')
        ->wait(1)
        ->click('button[data-test="tax-settings-save-button"]')
        ->wait(1)
        ->assertSee('Tax settings saved')
        ->assertNoJavaScriptErrors();

    $settings = TaxSettings::withoutGlobalScopes()->whereKey($store->getKey())->firstOrFail();

    expect($settings->prices_include_tax)->toBeFalse();
});

test('can view domain settings', function (): void {
    adminSettingsOpen($this)
        ->assertSee('Domains')
        ->assertSee('acme-fashion.test')
        ->assertNoJavaScriptErrors();
});
