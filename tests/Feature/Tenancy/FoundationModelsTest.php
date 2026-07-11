<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('foundation tables match the required schema', function () {
    expect(Schema::hasColumns('organizations', ['id', 'name', 'billing_email', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('stores', [
            'id', 'organization_id', 'name', 'handle', 'status', 'default_currency',
            'default_locale', 'timezone', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('store_domains', [
            'id', 'store_id', 'hostname', 'type', 'is_primary', 'tls_mode', 'created_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'id', 'email', 'password_hash', 'name', 'status', 'email_verified_at',
            'last_login_at', 'two_factor_secret', 'two_factor_recovery_codes',
            'two_factor_confirmed_at', 'remember_token', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('store_users', ['store_id', 'user_id', 'role', 'created_at']))->toBeTrue()
        ->and(Schema::hasColumns('store_settings', ['store_id', 'settings_json', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumn('users', 'password'))->toBeFalse();
});

test('foundation models expose typed relationships and casts', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();
    $domain = StoreDomain::factory()->for($store)->primary()->create();
    $settings = StoreSettings::factory()->for($store)->create([
        'settings_json' => ['checkout' => ['guest_enabled' => true]],
    ]);
    $user = User::factory()->create();
    $storeUser = StoreUser::factory()->owner()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);

    expect($organization->stores->sole()->is($store))->toBeTrue()
        ->and($store->organization->is($organization))->toBeTrue()
        ->and($store->status)->toBe(StoreStatus::Active)
        ->and($store->domains->sole()->is($domain))->toBeTrue()
        ->and($domain->type)->toBe(StoreDomainType::Storefront)
        ->and($domain->is_primary)->toBeTrue()
        ->and($store->settings->is($settings))->toBeTrue()
        ->and($settings->settings_json)->toBe(['checkout' => ['guest_enabled' => true]])
        ->and($storeUser->role)->toBe(StoreUserRole::Owner)
        ->and($user->roleForStore($store))->toBe(StoreUserRole::Owner)
        ->and($user->stores->sole()->is($store))->toBeTrue();
});

test('laravel authentication uses the password hash column', function () {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'secret-password',
    ]);

    expect($user->getAuthPasswordName())->toBe('password_hash')
        ->and(Hash::check('secret-password', $user->password_hash))->toBeTrue()
        ->and($user->password)->toBe($user->password_hash)
        ->and(Auth::validate([
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]))->toBeTrue();
});
