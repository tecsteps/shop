<?php

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminShippingSettingsApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminShippingSettingsApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminShippingSettingsApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

test('admin shipping settings api lists creates updates zones and adds rates', function (): void {
    $store = adminShippingSettingsApiStore();
    $user = adminShippingSettingsApiUser();
    $readToken = adminApiBearerToken($store, ['read-settings'], $user);
    $writeToken = adminApiBearerToken($store, ['write-settings'], $user);

    $this->withToken($readToken)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Domestic')
        ->assertJsonPath('data.0.rates.0.config_json.currency', $store->default_currency);

    $createResponse = $this->withToken($writeToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones", [
            'name' => 'Nordics API',
            'countries_json' => ['se', 'no'],
            'regions_json' => ['stockholm'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Nordics API')
        ->assertJsonPath('data.countries_json.0', 'SE')
        ->assertJsonPath('data.regions_json.0', 'STOCKHOLM');

    $zone = ShippingZone::withoutGlobalScopes()->findOrFail($createResponse->json('data.id'));

    $this->withToken($writeToken)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones/{$zone->getKey()}", [
            'name' => 'Nordics and Baltics API',
            'countries_json' => ['SE', 'DK'],
            'regions_json' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nordics and Baltics API')
        ->assertJsonPath('data.countries_json.1', 'DK');

    $rateResponse = $this->withToken($writeToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones/{$zone->getKey()}/rates", [
            'name' => 'API Express',
            'type' => 'flat',
            'config_json' => [
                'price_amount' => 1200,
                'currency' => $store->default_currency,
            ],
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'API Express')
        ->assertJsonPath('data.type', 'flat')
        ->assertJsonPath('data.config_json.price_amount', 1200);

    $rate = ShippingRate::withoutGlobalScopes()->findOrFail($rateResponse->json('data.id'));

    expect($zone->refresh()->countries_json)->toBe(['SE', 'DK'])
        ->and($rate->type)->toBe(ShippingRateType::Flat)
        ->and($rate->config_json['amount'])->toBe(1200);
});

test('admin shipping settings api enforces token abilities and store scope', function (): void {
    $store = adminShippingSettingsApiStore();
    $otherStore = Store::factory()->create();
    $zone = ShippingZone::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();
    $readToken = adminShippingSettingsApiToken($store, ['read-settings']);
    $writeToken = adminShippingSettingsApiToken($store, ['write-settings']);
    $otherStoreToken = adminShippingSettingsApiToken($otherStore, ['read-settings']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones")
        ->assertOk();

    $this->withToken($readToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones", [
            'name' => 'Read Only Zone',
            'countries_json' => ['FI'],
        ])
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones/{$zone->getKey()}/rates", [
            'name' => 'Token Rate',
            'type' => 'carrier',
            'config_json' => [
                'price_amount' => 999,
            ],
        ])
        ->assertCreated();
});

test('admin shipping settings api validates countries overlaps and rate types', function (): void {
    $store = adminShippingSettingsApiStore();
    $zone = ShippingZone::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    $this->withToken(adminApiBearerToken($store, ['write-settings'], adminShippingSettingsApiUser()))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones", [
            'name' => 'Overlap API',
            'countries_json' => ['DE'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['countries_json']);

    $this->withToken(adminApiBearerToken($store, ['write-settings'], adminShippingSettingsApiUser()))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/shipping/zones/{$zone->getKey()}/rates", [
            'name' => 'Invalid Rate',
            'type' => 'rocket',
            'config_json' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});
