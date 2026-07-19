<?php

use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\User;

test('storefront resolves the store from the request hostname', function () {
    $store = $this->createStore(['handle' => 'acme-fashion']);

    $response = $this->get('http://acme-fashion.test/');

    $response->assertOk();
    expect(app('current_store')->id)->toBe($store->id);
});

test('storefront returns 404 for an unknown hostname', function () {
    $this->get('http://missing-shop.test/')->assertNotFound();
});

test('storefront returns 503 when the store is suspended', function () {
    $this->createStore(['handle' => 'suspended-shop', 'status' => StoreStatus::Suspended]);

    $this->get('http://suspended-shop.test/')->assertServiceUnavailable();
});

test('admin resolves the store from the session', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, StoreUserRole::Owner);

    $response = $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin');

    $response->assertOk();
    expect(app('current_store')->id)->toBe($store->id);
});

test('admin returns 403 when the user has no store membership', function () {
    $store = $this->createStore();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin')
        ->assertForbidden();
});

test('admin returns 403 when the store is suspended', function () {
    $store = $this->createStore(['status' => StoreStatus::Suspended]);
    $user = $this->createUserWithRole($store, StoreUserRole::Admin);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin')
        ->assertForbidden();
});

test('admin returns 403 when no store is selected in the session', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, StoreUserRole::Owner);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
