<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('foundation models persist with expected relationships and casts', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create([
        'status' => StoreStatus::Active,
    ]);
    $domain = StoreDomain::factory()->for($store)->create([
        'type' => StoreDomainType::Storefront,
    ]);
    $settings = StoreSettings::factory()->for($store)->create([
        'settings_json' => ['checkout' => ['guest_checkout_enabled' => true]],
    ]);

    expect($organization->stores)->toHaveCount(1)
        ->and($store->fresh()->organization->is($organization))->toBeTrue()
        ->and($domain->fresh()->type)->toBe(StoreDomainType::Storefront)
        ->and($domain->fresh()->is_primary)->toBeTrue()
        ->and($settings->fresh()->settings_json)->toBe(['checkout' => ['guest_checkout_enabled' => true]])
        ->and($store->fresh()->status)->toBe(StoreStatus::Active);
});

test('users expose their store role through the pivot model', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    DB::table('store_users')->insert([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    expect($user->roleForStore($store))->toBe(StoreUserRole::Owner);
});
