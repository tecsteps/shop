<?php

use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

function tenantResolutionSeedStore(string $hostname, string $status = 'active'): int
{
    expect(Schema::hasTable('organizations'))->toBeTrue('Missing organizations table for tenant resolution tests.');
    expect(Schema::hasTable('stores'))->toBeTrue('Missing stores table for tenant resolution tests.');
    expect(Schema::hasTable('store_domains'))->toBeTrue('Missing store_domains table for tenant resolution tests.');

    $now = now();
    $random = Str::lower(Str::random(6));

    $organizationId = DB::table('organizations')->insertGetId([
        'name' => 'Org '.$random,
        'billing_email' => "billing-{$random}@example.test",
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $storeId = DB::table('stores')->insertGetId([
        'organization_id' => $organizationId,
        'name' => 'Store '.$random,
        'handle' => 'store-'.$random,
        'status' => $status,
        'default_currency' => 'USD',
        'default_locale' => 'en',
        'timezone' => 'UTC',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('store_domains')->insert([
        'store_id' => $storeId,
        'hostname' => $hostname,
        'type' => 'storefront',
        'is_primary' => true,
        'tls_mode' => 'managed',
        'created_at' => $now,
    ]);

    return $storeId;
}

function tenantResolutionRegisterProbe(string $uri): void
{
    Route::middleware([ResolveStore::class])->get($uri, static fn () => response()->noContent());
}

test('returns 404 for unknown hostname', function (): void {
    expect(class_exists(ResolveStore::class))->toBeTrue('ResolveStore middleware is missing.');

    $uri = '/__tenant-resolution-unknown';
    tenantResolutionRegisterProbe($uri);

    $this->get('http://nonexistent.test'.$uri)
        ->assertNotFound();
});

test('returns 503 for suspended store on storefront requests', function (): void {
    expect(class_exists(ResolveStore::class))->toBeTrue('ResolveStore middleware is missing.');

    $hostname = 'suspended-store.test';
    tenantResolutionSeedStore($hostname, 'suspended');

    $uri = '/__tenant-resolution-suspended';
    tenantResolutionRegisterProbe($uri);

    $this->get("http://{$hostname}{$uri}")
        ->assertStatus(503);
});
