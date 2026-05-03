<?php

use App\Enums\TaxMode;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminSettingsTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Settings API test', $abilities)['plain_text_token'];
}

test('admin shipping api lists zones creates zones updates zones and adds rates', function (): void {
    $indexUrl = route('api.admin.shipping.zones.index', $this->store);

    $this->getJson($indexUrl)->assertUnauthorized();

    $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
        ->getJson($indexUrl)
        ->assertForbidden();

    $this->withToken(adminSettingsTokenFor($this, ['read-settings']))
        ->getJson($indexUrl)
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Germany')
        ->assertJsonPath('data.0.rates.0.name', 'Standard Shipping');

    $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
        ->postJson(route('api.admin.shipping.zones.store', $this->store), [
            'name' => 'Duplicate Germany',
            'countries_json' => ['DE'],
        ])
        ->assertInvalid(['countries_json']);

    $response = $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
        ->postJson(route('api.admin.shipping.zones.store', $this->store), [
            'name' => 'United States',
            'countries_json' => ['us'],
            'regions_json' => ['CA'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'United States')
        ->assertJsonPath('data.countries_json.0', 'US');

    $zone = ShippingZone::query()->whereKey($response->json('data.id'))->firstOrFail();

    $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
        ->putJson(route('api.admin.shipping.zones.update', [$this->store, $zone]), [
            'name' => 'United Kingdom',
            'countries_json' => ['GB'],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'United Kingdom')
        ->assertJsonPath('data.countries_json.0', 'GB');

    $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
        ->postJson(route('api.admin.shipping.zones.rates.store', [$this->store, $zone]), [
            'name' => 'API Express',
            'type' => 'flat',
            'config_json' => ['amount' => 1500, 'currency' => 'EUR'],
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonFragment(['name' => 'API Express']);

    expect($zone->rates()->where('name', 'API Express')->exists())->toBeTrue();
});

test('admin tax settings api shows and updates tax configuration', function (): void {
    $showUrl = route('api.admin.tax.settings.show', $this->store);

    $this->withToken(adminSettingsTokenFor($this, ['read-settings']))
        ->getJson($showUrl)
        ->assertOk()
        ->assertJsonPath('data.mode', TaxMode::Manual->value)
        ->assertJsonPath('data.config_json.default_rate_basis_points', 1900);

    $this->withToken(adminSettingsTokenFor($this, ['read-settings']))
        ->putJson(route('api.admin.tax.settings.update', $this->store), [
            'mode' => 'provider',
            'provider' => 'stripe_tax',
            'prices_include_tax' => false,
            'config_json' => ['stripe_tax_settings_id' => 'txr_api_123'],
        ])
        ->assertForbidden();

    $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
        ->putJson(route('api.admin.tax.settings.update', $this->store), [
            'mode' => 'provider',
            'provider' => 'stripe_tax',
            'prices_include_tax' => false,
            'config_json' => ['stripe_tax_settings_id' => 'txr_api_123'],
        ])
        ->assertOk()
        ->assertJsonPath('data.mode', TaxMode::Provider->value)
        ->assertJsonPath('data.provider', 'stripe_tax')
        ->assertJsonPath('data.config_json.stripe_tax_settings_id', 'txr_api_123');

    expect(TaxSettings::withoutGlobalScopes()->where('store_id', $this->store->id)->firstOrFail()->provider)->toBe('stripe_tax');
});

test('admin settings api enforces store scoped token access and zone lookup', function (): void {
    $otherStoreZone = ShippingZone::withoutGlobalScopes()
        ->where('store_id', $this->otherStore->id)
        ->first();

    $this->withToken(adminSettingsTokenFor($this, ['read-settings']))
        ->getJson(route('api.admin.shipping.zones.index', $this->otherStore))
        ->assertForbidden();

    if ($otherStoreZone instanceof ShippingZone) {
        $this->withToken(adminSettingsTokenFor($this, ['write-settings']))
            ->putJson(route('api.admin.shipping.zones.update', [$this->store, $otherStoreZone]), [
                'name' => 'Cross tenant',
            ])
            ->assertNotFound();
    }
});
