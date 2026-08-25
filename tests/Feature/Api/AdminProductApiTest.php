<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;

function adminToken(User $user, array $abilities): string
{
    return $user->createToken('test', $abilities)->plainTextToken;
}

it('lists products with authentication', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    Product::factory()->count(3)->create(['store_id' => $store->id]);

    $token = adminToken($user, ['read-products']);

    $this->withToken($token)->getJson('/api/admin/v1/stores/'.$store->id.'/products')
        ->assertStatus(200)
        ->assertJsonPath('meta.total', 3);
});

it('creates a product via API', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = adminToken($user, ['write-products']);

    $this->withToken($token)->postJson('/api/admin/v1/stores/'.$store->id.'/products', [
        'title' => 'API Product',
        'status' => 'draft',
    ])->assertStatus(201)
        ->assertJsonPath('data.title', 'API Product');
});

it('updates a product via API', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $product = Product::factory()->create(['store_id' => $store->id, 'title' => 'Old']);
    $token = adminToken($user, ['write-products']);

    $this->withToken($token)->putJson('/api/admin/v1/stores/'.$store->id.'/products/'.$product->id, [
        'title' => 'New Title',
    ])->assertStatus(200)
        ->assertJsonPath('data.title', 'New Title');
});

it('requires write-products ability for mutations', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = adminToken($user, ['read-products']);

    $this->withToken($token)->postJson('/api/admin/v1/stores/'.$store->id.'/products', [
        'title' => 'Forbidden',
    ])->assertStatus(403);
});

it('returns 401 without token', function () {
    $store = Store::factory()->create();

    $this->getJson('/api/admin/v1/stores/'.$store->id.'/products')->assertStatus(401);
});
