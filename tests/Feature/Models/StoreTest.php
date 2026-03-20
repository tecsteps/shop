<?php

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\StoreUser;
use App\Models\User;

it('belongs to an organization', function () {
    $store = Store::factory()->create();

    expect($store->organization)->toBeInstanceOf(Organization::class);
});

it('has many store domains', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->count(2)->create(['store_id' => $store->id]);

    expect($store->domains)->toHaveCount(2);
});

it('belongs to many users through store_users', function () {
    $store = Store::factory()->create();
    $users = User::factory()->count(2)->create();

    $store->users()->attach($users[0]->id, ['role' => 'owner']);
    $store->users()->attach($users[1]->id, ['role' => 'admin']);

    $store->refresh();
    expect($store->users)->toHaveCount(2);

    $pivot = $store->users->first()->pivot;
    expect($pivot)->toBeInstanceOf(StoreUser::class);
    expect($pivot->role)->not->toBeNull();
});

it('has one store settings record', function () {
    $store = Store::factory()->create();
    StoreSettings::factory()->create(['store_id' => $store->id]);

    expect($store->settings)->toBeInstanceOf(StoreSettings::class);
});

it('factory creates valid records with all defaults', function () {
    $store = Store::factory()->create();

    expect($store->name)->not->toBeEmpty();
    expect($store->handle)->not->toBeEmpty();
    expect($store->status)->toBe(StoreStatus::Active);
    expect($store->default_currency)->toBe('USD');
    expect($store->default_locale)->toBe('en');
    expect($store->timezone)->toBe('UTC');
});

it('casts status to StoreStatus enum', function () {
    $store = Store::factory()->create(['status' => 'active']);

    expect($store->status)->toBeInstanceOf(StoreStatus::class);
    expect($store->status)->toBe(StoreStatus::Active);
});
