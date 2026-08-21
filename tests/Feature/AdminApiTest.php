<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();
});

test('store members can use the versioned admin catalog API', function (): void {
    $token = $this->admin->createToken('catalog-manager', ['read-products', 'write-collections'])->plainTextToken;

    $this->withToken($token)
        ->getJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/products")
        ->assertOk()
        ->assertJsonPath('meta.total', 20);

    $this->withToken($token)
        ->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/collections", [
            'title' => 'API Collection',
            'type' => 'manual',
            'product_ids' => [],
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'API Collection');
});

test('admin API rejects a store id outside the current tenant', function (): void {
    $otherStore = Store::factory()->create();
    $token = $this->admin->createToken('catalog-reader', ['read-products'])->plainTextToken;

    $this->withToken($token)
        ->getJson("http://shop.test/api/admin/v1/stores/{$otherStore->getKey()}/products")
        ->assertForbidden();
});
