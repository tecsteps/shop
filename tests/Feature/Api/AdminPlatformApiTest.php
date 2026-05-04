<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminPlatformApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminPlatformApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminPlatformApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

test('platform api creates organizations and stores for platform admin bearer tokens', function (): void {
    $store = adminPlatformApiStore();
    $user = adminPlatformApiUser();
    $token = adminApiBearerToken($store, ['manage-platform'], $user);

    $organizationResponse = $this->withToken($token)
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Platform API Org',
            'billing_email' => 'billing@platform-api.test',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Platform API Org')
        ->assertJsonPath('data.billing_email', 'billing@platform-api.test');

    $organizationId = $organizationResponse->json('data.id');

    $this->withToken($token)
        ->postJson('/api/admin/v1/platform/stores', [
            'organization_id' => $organizationId,
            'name' => 'Platform API Store',
            'handle' => 'platform-api-store',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Platform API Store')
        ->assertJsonPath('data.handle', 'platform-api-store')
        ->assertJsonPath('data.status', 'active');

    $store = Store::query()->where('handle', 'platform-api-store')->firstOrFail();

    expect(StoreSettings::query()->whereKey($store->getKey())->exists())->toBeTrue()
        ->and(DB::table('store_users')
            ->where('store_id', $store->getKey())
            ->where('user_id', $user->getKey())
            ->where('role', 'owner')
            ->exists())->toBeTrue();
});

test('platform api rejects non platform admin tokens and tokens without manage platform ability', function (): void {
    $store = adminPlatformApiStore();
    $ordinaryOwner = User::factory()->create([
        'email' => 'ordinary-owner@example.test',
        'is_platform_admin' => false,
    ]);
    $staff = User::factory()->create([
        'email' => 'platform-staff@example.test',
    ]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $ordinaryOwner->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $staff->getKey(),
        'role' => StoreUserRole::Staff->value,
        'created_at' => now(),
    ]);
    $readToken = adminPlatformApiToken($store, ['read-products']);
    $ordinaryOwnerToken = adminApiBearerToken($store, ['manage-platform'], $ordinaryOwner);
    $staffToken = adminApiBearerToken($store, ['manage-platform'], $staff);

    $this->withToken($ordinaryOwnerToken)
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Ordinary Owner Org',
            'billing_email' => 'ordinary-owner@example.test',
        ])
        ->assertForbidden();

    $this->withToken($staffToken)
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Forbidden Org',
            'billing_email' => 'forbidden@example.test',
        ])
        ->assertForbidden();

    $this->withToken($readToken['plain_text'])
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Forbidden Token Org',
            'billing_email' => 'forbidden-token@example.test',
        ])
        ->assertForbidden();
});

test('admin api routes use the named admin token rate limiter', function (): void {
    $storeProductRoute = Route::getRoutes()->getByName('api.admin.v1.products.index');
    $platformRoute = Route::getRoutes()->getByName('api.admin.v1.platform.organizations.store');

    expect($storeProductRoute)->not->toBeNull()
        ->and($storeProductRoute->gatherMiddleware())->toContain('auth:sanctum')
        ->and($storeProductRoute->gatherMiddleware())->toContain('throttle:api.admin')
        ->and($platformRoute)->not->toBeNull()
        ->and($platformRoute->gatherMiddleware())->toContain('auth:sanctum')
        ->and($platformRoute->gatherMiddleware())->toContain('throttle:api.admin');
});

test('platform api accepts manage platform bearer tokens', function (): void {
    $store = adminPlatformApiStore();
    $token = adminPlatformApiToken($store, ['manage-platform']);

    $this->withToken($token['plain_text'])
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Token Platform Org',
            'billing_email' => 'token-platform@example.test',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Token Platform Org');

    expect($token['token']->refresh()->last_used_at)->not->toBeNull();
});

test('store me and invite endpoints expose membership and enforce role or ability', function (): void {
    $store = adminPlatformApiStore();
    $user = adminPlatformApiUser();
    $staff = User::factory()->create([
        'email' => 'store-staff@example.test',
    ]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $staff->getKey(),
        'role' => 'staff',
        'created_at' => now(),
    ]);
    $readToken = adminPlatformApiToken($store, ['read-products']);
    $manageToken = adminPlatformApiToken($store, ['manage-platform']);
    $userToken = adminApiBearerToken($store, ['*'], $user);
    $staffToken = adminApiBearerToken($store, ['manage-platform'], $staff);

    $this->withToken($readToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/invites", [
            'email' => 'new-staff@example.test',
            'role' => 'staff',
        ])
        ->assertForbidden();

    $this->withToken($manageToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/invites", [
            'email' => 'new-staff@example.test',
            'role' => 'staff',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'new-staff@example.test')
        ->assertJsonPath('data.role', 'staff');

    $this->withToken($userToken)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/me")
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@acme.test')
        ->assertJsonPath('data.role', 'owner')
        ->assertJsonPath('data.permissions.1', 'read-products');

    $this->withToken($staffToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/invites", [
            'email' => 'new-staff@example.test',
            'role' => 'staff',
        ])
        ->assertForbidden();

    $this->withToken($manageToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/invites", [
            'email' => 'admin@acme.test',
            'role' => 'admin',
        ])
        ->assertStatus(409);
});
