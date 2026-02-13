<?php

use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;

it('resolves store by hostname for storefront requests', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();
    StoreDomain::factory()->for($store)->create([
        'hostname' => 'my-shop.example.com',
    ]);

    $response = $this->get('http://my-shop.example.com/');

    $response->assertOk();
});

it('returns 404 when store hostname is not found', function () {
    $response = $this->get('/', ['Host' => 'nonexistent.example.com']);

    $response->assertNotFound();
});

it('resolves store from session for admin routes', function () {
    $context = createStoreContext();

    actingAsAdmin($context['user'], $context['store'])
        ->get('/admin/dashboard')
        ->assertOk();
});

it('returns 404 when admin has no store in session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertNotFound();
});

it('returns 503 when store is suspended for admin routes', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->suspended()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Owner->value]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/dashboard')
        ->assertServiceUnavailable();
});

it('returns 404 when user does not belong to the store', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/dashboard')
        ->assertNotFound();
});
