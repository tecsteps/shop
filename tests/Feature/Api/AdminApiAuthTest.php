<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-14 12:00:00'));
    Cache::flush();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function adminApiUrl(string $hostname, string $path): string
{
    return 'http://'.$hostname.$path;
}

function adminApiCreateStore(string $hostname): Store
{
    $organization = Organization::query()->create([
        'name' => 'Admin API Org',
        'billing_email' => 'billing+admin-api@example.test',
    ]);

    $store = Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Admin API Store',
        'handle' => 'admin-api-store',
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);

    StoreDomain::query()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
        'type' => 'api',
        'is_primary' => true,
        'tls_mode' => 'managed',
        'created_at' => now(),
    ]);

    return $store;
}

function adminApiCreateAdminMember(Store $store): User
{
    /** @var User $user */
    $user = User::factory()->create([
        'name' => 'Admin API User',
        'email' => 'admin-api@example.test',
    ]);

    DB::table('store_users')->insert([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'created_at' => now(),
    ]);

    return $user;
}

test('admin me endpoint requires sanctum authentication', function (): void {
    $hostname = 'admin-me-auth.test';
    adminApiCreateStore($hostname);

    $this->getJson(
        adminApiUrl($hostname, '/api/admin/v1/me'),
    )->assertUnauthorized();
});

test('authenticated admin can access admin me endpoint', function (): void {
    $hostname = 'admin-me-ok.test';
    $store = adminApiCreateStore($hostname);
    $user = adminApiCreateAdminMember($store);
    $this->actingAs($user, 'web');

    $this->getJson(
        adminApiUrl($hostname, '/api/admin/v1/me'),
    )->assertOk()
        ->assertJsonPath('user_id', $user->id);
});

test('admin products collections and discounts endpoints require auth', function (): void {
    $hostname = 'admin-auth-required.test';
    $store = adminApiCreateStore($hostname);

    $this->getJson(
        adminApiUrl($hostname, "/api/admin/v1/stores/{$store->id}/products"),
    )->assertUnauthorized();

    $this->getJson(
        adminApiUrl($hostname, "/api/admin/v1/stores/{$store->id}/collections"),
    )->assertUnauthorized();

    $this->getJson(
        adminApiUrl($hostname, "/api/admin/v1/stores/{$store->id}/discounts"),
    )->assertUnauthorized();
});

test('authenticated admin can access products collections and discounts endpoints', function (): void {
    $hostname = 'admin-authenticated-lists.test';
    $store = adminApiCreateStore($hostname);
    $user = adminApiCreateAdminMember($store);
    $this->actingAs($user, 'web');

    $this->getJson(
        adminApiUrl($hostname, "/api/admin/v1/stores/{$store->id}/products"),
    )->assertOk();

    $this->getJson(
        adminApiUrl($hostname, "/api/admin/v1/stores/{$store->id}/collections"),
    )->assertOk();

    $this->getJson(
        adminApiUrl($hostname, "/api/admin/v1/stores/{$store->id}/discounts"),
    )->assertOk();
});
