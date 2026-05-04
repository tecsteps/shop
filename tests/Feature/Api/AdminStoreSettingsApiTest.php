<?php

use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\User;
use App\Services\WebhookService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminStoreSettingsApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminStoreSettingsApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\OauthToken, plain_text: string}
 */
function adminStoreSettingsApiToken(Store $store, array $abilities): array
{
    return app(WebhookService::class)->createApiToken($store, 'Store settings integration', $abilities);
}

test('admin store settings api shows and updates general settings', function (): void {
    $store = adminStoreSettingsApiStore();
    $user = adminStoreSettingsApiUser();

    $this->actingAs($user)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/settings")
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme Fashion')
        ->assertJsonPath('data.default_currency', 'EUR')
        ->assertJsonPath('data.settings_json.announcement.enabled', true)
        ->assertJsonPath('data.domains.0.is_primary', true);

    $this->actingAs($user)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/settings", [
            'name' => 'Acme API Store',
            'default_currency' => 'USD',
            'default_locale' => 'de',
            'timezone' => 'Europe/Paris',
            'settings_json' => [
                'announcement' => [
                    'enabled' => false,
                    'text' => 'API announcement',
                ],
                'checkout' => [
                    'terms_required' => true,
                    'terms_url' => 'https://example.test/terms',
                ],
                'bank_transfer_cancel_days' => 10,
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme API Store')
        ->assertJsonPath('data.default_currency', 'USD')
        ->assertJsonPath('data.default_locale', 'de')
        ->assertJsonPath('data.timezone', 'Europe/Paris')
        ->assertJsonPath('data.settings_json.announcement.enabled', false)
        ->assertJsonPath('data.settings_json.checkout.terms_required', true)
        ->assertJsonPath('data.settings_json.notifications.sender_email', 'no-reply@shop.test');

    $settings = StoreSettings::query()->whereKey($store->getKey())->firstOrFail();

    expect($store->refresh()->name)->toBe('Acme API Store')
        ->and($store->default_currency)->toBe('USD')
        ->and($settings->settings_json['announcement']['text'])->toBe('API announcement')
        ->and($settings->settings_json['checkout']['terms_url'])->toBe('https://example.test/terms')
        ->and($settings->settings_json['notifications']['sender_email'])->toBe('no-reply@shop.test');
});

test('admin store settings api enforces token abilities and store scope', function (): void {
    $store = adminStoreSettingsApiStore();
    $otherStore = Store::factory()->create();
    $readToken = adminStoreSettingsApiToken($store, ['read-settings']);
    $writeToken = adminStoreSettingsApiToken($store, ['write-settings']);
    $otherStoreToken = adminStoreSettingsApiToken($otherStore, ['read-settings']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/settings")
        ->assertOk();

    $this->withToken($readToken['plain_text'])
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/settings", [
            'name' => 'Read Only API Store',
        ])
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/settings")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/settings", [
            'settings_json' => [
                'notifications' => [
                    'low_stock_threshold' => 12,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.settings_json.notifications.low_stock_threshold', 12);
});

test('admin store settings api validates defaults and settings shape', function (): void {
    $store = adminStoreSettingsApiStore();

    $this->actingAs(adminStoreSettingsApiUser())
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/settings", [
            'default_currency' => 'BTC',
            'default_locale' => 'es',
            'timezone' => 'Mars/Olympus',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['default_currency', 'default_locale', 'timezone']);

    $this->actingAs(adminStoreSettingsApiUser())
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/settings", [
            'settings_json' => ['invalid-list-value'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings_json']);

    $this->actingAs(adminStoreSettingsApiUser())
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/settings", [
            'settings_json' => [
                'checkout' => [
                    'terms_url' => 'not-a-url',
                    'payment_hold_hours' => 0,
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings_json.checkout.terms_url', 'settings_json.checkout.payment_hold_hours']);
});
