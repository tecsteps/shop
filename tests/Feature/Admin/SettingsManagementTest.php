<?php

use App\Enums\ShippingRateType;
use App\Enums\StoreUserRole;
use App\Enums\TaxMode;
use App\Livewire\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminSettingsStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function adminSettingsUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminSettingsUserWithRole(Store $store, StoreUserRole $role): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), [
        'role' => $role->value,
        'created_at' => now(),
    ]);

    return $user;
}

test('settings routes render for owners and reject staff', function (): void {
    $store = adminSettingsStore();
    $owner = adminSettingsUser();
    $staff = adminSettingsUserWithRole($store, StoreUserRole::Staff);

    foreach (['/admin/settings', '/admin/settings/shipping', '/admin/settings/taxes'] as $path) {
        $this->actingAs($owner)
            ->withSession(['current_store_id' => $store->getKey()])
            ->get($path)
            ->assertSuccessful();

        $this->actingAs($staff)
            ->withSession(['current_store_id' => $store->getKey()])
            ->get($path)
            ->assertForbidden();
    }
});

test('general settings update store defaults and domains', function (): void {
    $store = adminSettingsStore();
    $user = adminSettingsUser();

    Livewire::actingAs($user)
        ->test(AdminSettingsIndex::class)
        ->set('storeName', 'Acme Atelier')
        ->set('defaultCurrency', 'GBP')
        ->set('defaultLocale', 'de')
        ->set('timezone', 'Europe/Berlin')
        ->set('announcementEnabled', true)
        ->set('announcementText', 'Spring edits now live')
        ->set('guestCheckoutEnabled', false)
        ->call('save')
        ->assertHasNoErrors()
        ->set('newHostname', 'atelier.test')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertHasNoErrors();

    $settings = StoreSettings::query()->whereKey($store->getKey())->firstOrFail();

    expect($store->refresh()->name)->toBe('Acme Atelier')
        ->and($store->default_currency)->toBe('GBP')
        ->and($store->default_locale)->toBe('de')
        ->and($settings->settings_json['announcement']['text'])->toBe('Spring edits now live')
        ->and($settings->settings_json['checkout']['guest_checkout_enabled'])->toBeFalse()
        ->and(StoreDomain::query()->where('hostname', 'atelier.test')->where('store_id', $store->getKey())->exists())->toBeTrue();
});

test('shipping settings manage zones rates and address tests', function (): void {
    $store = adminSettingsStore();
    $user = adminSettingsUser();

    Livewire::actingAs($user)
        ->test(AdminSettingsShipping::class)
        ->set('zoneName', 'Nordics')
        ->set('zoneCountries', 'SE, NO')
        ->call('saveZone')
        ->assertHasNoErrors();

    $zone = ShippingZone::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('name', 'Nordics')
        ->firstOrFail();

    Livewire::actingAs($user)
        ->test(AdminSettingsShipping::class)
        ->call('addRate', $zone->getKey())
        ->set('rateName', 'Nordic Standard')
        ->set('rateType', ShippingRateType::Flat->value)
        ->set('rateAmount', '12.50')
        ->call('saveRate')
        ->assertHasNoErrors()
        ->set('testCountry', 'SE')
        ->call('testShippingAddress')
        ->assertSet('testResult.zone', 'Nordics');

    $rate = ShippingRate::withoutGlobalScopes()
        ->where('zone_id', $zone->getKey())
        ->where('name', 'Nordic Standard')
        ->firstOrFail();

    expect($zone->countries_json)->toBe(['SE', 'NO'])
        ->and($rate->type)->toBe(ShippingRateType::Flat)
        ->and($rate->config_json['amount'])->toBe(1250);
});

test('tax settings save manual and provider configuration', function (): void {
    $store = adminSettingsStore();
    $user = adminSettingsUser();

    Livewire::actingAs($user)
        ->test(AdminSettingsTaxes::class)
        ->set('manualRates', [
            ['country' => 'DE', 'name' => 'VAT', 'rate_percentage' => '19.00'],
            ['country' => 'FR', 'name' => 'TVA', 'rate_percentage' => '20.00'],
        ])
        ->set('pricesIncludeTax', true)
        ->call('save')
        ->assertHasNoErrors();

    $manual = TaxSettings::withoutGlobalScopes()->whereKey($store->getKey())->firstOrFail();

    expect($manual->mode)->toBe(TaxMode::Manual)
        ->and($manual->prices_include_tax)->toBeTrue()
        ->and($manual->config_json['rates'][1]['country'])->toBe('FR')
        ->and($manual->config_json['rates'][1]['rate_bps'])->toBe(2000);

    Livewire::actingAs($user)
        ->test(AdminSettingsTaxes::class)
        ->set('mode', TaxMode::Provider->value)
        ->set('provider', 'stripe_tax')
        ->set('providerApiKey', 'sk_test_tax')
        ->call('save')
        ->assertHasNoErrors();

    $provider = TaxSettings::withoutGlobalScopes()->whereKey($store->getKey())->firstOrFail();

    expect($provider->mode)->toBe(TaxMode::Provider)
        ->and($provider->provider)->toBe('stripe_tax')
        ->and($provider->config_json['provider_api_key'])->toBe('sk_test_tax');
});
