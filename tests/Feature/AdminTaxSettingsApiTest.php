<?php

use App\Models\TaxSetting;
use App\Models\User;
use Illuminate\Support\Str;

function taxSettingsToken(User $user, int $storeId): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $storeId,
        'name' => 'tax-settings-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => ['read-settings', 'write-settings'],
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

it('returns the documented tax settings resource and saves provider configuration', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = taxSettingsToken($user, $store->id);
    $url = "/api/admin/v1/stores/{$store->id}/tax/settings";

    $this->withToken($token)->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.store_id', $store->id)
        ->assertJsonPath('data.mode', 'manual')
        ->assertJsonPath('data.provider', 'none')
        ->assertJsonPath('data.config_json.default_tax_rate', 0)
        ->assertJsonPath('data.config_json.tax_rates', []);

    $this->withToken($token)->putJson($url, [
        'mode' => 'provider',
        'provider' => 'stripe_tax',
        'prices_include_tax' => false,
        'config_json' => [
            'stripe_tax_settings_id' => 'txr_shop_123',
            'fallback' => 'block',
            'default_tax_rate' => 1900,
            'tax_rates' => [
                ['country_code' => 'DE', 'rate' => 1900, 'name' => 'MwSt', 'shipping_taxed' => true],
                ['country_code' => 'AT', 'rate' => 2000],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'provider')
        ->assertJsonPath('data.provider', 'stripe_tax')
        ->assertJsonPath('data.config_json.fallback', 'block')
        ->assertJsonPath('data.config_json.tax_rates.0.country_code', 'DE');

    $this->withToken($token)->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.config_json.default_tax_rate', 1900)
        ->assertJsonPath('data.config_json.tax_rates.1.rate', 2000);

    $settings = TaxSetting::query()->findOrFail($store->id);
    expect($settings->taxRatesForCalculator())->toBe(['DE' => 1900, 'AT' => 2000])
        ->and($settings->defaultTaxRateForCalculator())->toBe(1900);
});

it('validates tax mode, provider, rate bounds, and object-shaped configuration', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = taxSettingsToken($user, $store->id);
    $url = "/api/admin/v1/stores/{$store->id}/tax/settings";

    $this->withToken($token)->putJson($url, [
        'mode' => 'provider',
        'prices_include_tax' => true,
        'config_json' => ['default_tax_rate' => 10001],
    ])->assertUnprocessable()->assertInvalid(['provider', 'config_json.default_tax_rate']);

    $this->withToken($token)->call('PUT', $url, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer '.$token,
    ], json_encode([
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => [],
    ], JSON_THROW_ON_ERROR))->assertUnprocessable()->assertInvalid(['config_json']);
});
