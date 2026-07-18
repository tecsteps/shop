<?php

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves the store from the request hostname', function () {
    $store = Store::factory()->create(['name' => 'Acme Fashion']);
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'acme-fashion.test',
        'is_primary' => true,
    ]);

    $this->get('http://acme-fashion.test/storefront-ping')
        ->assertSuccessful()
        ->assertSee('store:'.$store->id);
});

it('returns 404 for unknown hostnames', function () {
    $this->get('http://unknown-store.test/storefront-ping')
        ->assertNotFound();
});

it('returns 503 for suspended stores on the storefront', function () {
    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'suspended.test',
    ]);

    $this->get('http://suspended.test/storefront-ping')
        ->assertStatus(503);
});

it('resolves the admin store from the session for authenticated users', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/store-ping')
        ->assertSuccessful()
        ->assertSee('store:'.$store->id);
});
