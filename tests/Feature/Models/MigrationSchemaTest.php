<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('organizations table has correct columns', function () {
    expect(Schema::hasColumns('organizations', ['id', 'name', 'billing_email', 'created_at', 'updated_at']))->toBeTrue();
});

it('stores table has correct columns', function () {
    expect(Schema::hasColumns('stores', [
        'id', 'organization_id', 'name', 'handle', 'status',
        'default_currency', 'default_locale', 'timezone', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('store_domains table has correct columns', function () {
    expect(Schema::hasColumns('store_domains', [
        'id', 'store_id', 'hostname', 'type', 'is_primary', 'tls_mode', 'created_at',
    ]))->toBeTrue();
});

it('users table has status and last_login_at columns', function () {
    expect(Schema::hasColumns('users', ['status', 'last_login_at']))->toBeTrue();
});

it('store_users table has composite primary key columns', function () {
    expect(Schema::hasColumns('store_users', ['store_id', 'user_id', 'role', 'created_at']))->toBeTrue();
});

it('store_settings table has store_id as primary key', function () {
    expect(Schema::hasColumns('store_settings', ['store_id', 'settings_json', 'updated_at']))->toBeTrue();
});

it('customers table has correct columns', function () {
    expect(Schema::hasColumns('customers', [
        'id', 'store_id', 'email', 'password', 'name', 'marketing_opt_in', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('customer_password_reset_tokens table exists', function () {
    expect(Schema::hasTable('customer_password_reset_tokens'))->toBeTrue();
    expect(Schema::hasColumns('customer_password_reset_tokens', ['email', 'store_id', 'token', 'created_at']))->toBeTrue();
});

it('cascading deletes work correctly for organizations', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    StoreDomain::factory()->create(['store_id' => $store->id]);
    StoreSettings::factory()->create(['store_id' => $store->id]);
    $user = User::factory()->create();
    $store->users()->attach($user->id, ['role' => 'owner']);

    $organization->delete();

    expect(Store::count())->toBe(0);
    expect(StoreDomain::count())->toBe(0);
    expect(StoreSettings::count())->toBe(0);
    expect(\Illuminate\Support\Facades\DB::table('store_users')->count())->toBe(0);
});

it('seeder creates sample foundation data', function () {
    $this->seed();

    expect(Organization::count())->toBeGreaterThanOrEqual(1);
    expect(Store::count())->toBeGreaterThanOrEqual(1);
    expect(StoreDomain::count())->toBeGreaterThanOrEqual(1);
    expect(\Illuminate\Support\Facades\DB::table('store_users')->count())->toBeGreaterThanOrEqual(1);
    expect(StoreSettings::count())->toBeGreaterThanOrEqual(1);
});
