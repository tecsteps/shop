<?php

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminTaxSettingsApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminTaxSettingsApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminTaxSettingsApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

test('admin tax settings api shows and updates manual settings', function (): void {
    $store = adminTaxSettingsApiStore();
    $user = adminTaxSettingsApiUser();
    $readToken = adminApiBearerToken($store, ['read-settings'], $user);
    $writeToken = adminApiBearerToken($store, ['write-settings'], $user);

    $this->withToken($readToken)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings")
        ->assertOk()
        ->assertJsonPath('data.store_id', $store->getKey())
        ->assertJsonPath('data.mode', 'manual')
        ->assertJsonPath('data.config_json.default_tax_rate', 1900);

    $this->withToken($writeToken)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings", [
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => true,
            'config_json' => [
                'default_tax_rate' => 2100,
                'tax_rates' => [
                    [
                        'country_code' => 'de',
                        'rate' => 2100,
                        'name' => 'VAT',
                        'shipping_taxed' => true,
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.prices_include_tax', true)
        ->assertJsonPath('data.config_json.default_tax_rate', 2100)
        ->assertJsonPath('data.config_json.tax_rates.0.country_code', 'DE');

    $settings = TaxSettings::withoutGlobalScopes()->whereKey($store->getKey())->firstOrFail();

    expect($settings->mode)->toBe(TaxMode::Manual)
        ->and($settings->provider)->toBe('none')
        ->and($settings->prices_include_tax)->toBeTrue()
        ->and($settings->config_json['default_rate_bps'])->toBe(2100)
        ->and($settings->config_json['rates'][0]['country'])->toBe('DE');
});

test('admin tax settings api enforces token abilities and store scope', function (): void {
    $store = adminTaxSettingsApiStore();
    $otherStore = Store::factory()->create();
    $readToken = adminTaxSettingsApiToken($store, ['read-settings']);
    $writeToken = adminTaxSettingsApiToken($store, ['write-settings']);
    $otherStoreToken = adminTaxSettingsApiToken($otherStore, ['read-settings']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings")
        ->assertOk();

    $this->withToken($readToken['plain_text'])
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings", [
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => [],
        ])
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings", [
            'mode' => 'provider',
            'provider' => 'stripe_tax',
            'prices_include_tax' => false,
            'config_json' => [
                'stripe_tax_settings_id' => 'txr_api',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'provider')
        ->assertJsonPath('data.provider', 'stripe_tax');
});

test('admin tax settings api validates provider and rate payloads', function (): void {
    $store = adminTaxSettingsApiStore();

    $this->withToken(adminApiBearerToken($store, ['write-settings'], adminTaxSettingsApiUser()))
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings", [
            'mode' => 'provider',
            'prices_include_tax' => false,
            'config_json' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['provider']);

    $this->withToken(adminApiBearerToken($store, ['write-settings'], adminTaxSettingsApiUser()))
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/tax/settings", [
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => [
                'tax_rates' => [
                    [
                        'country_code' => 'DEU',
                        'rate' => 12000,
                        'name' => 'VAT',
                    ],
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['config_json.tax_rates.0.country_code', 'config_json.tax_rates.0.rate']);
});
