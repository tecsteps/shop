<?php

use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedAdminContext(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    \DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Admin->value,
        'created_at' => now(),
    ]);

    return [$store, $user];
}

it('requires authentication', function () {
    $this->getJson('/api/admin/v1/stores/1/products')->assertUnauthorized();
});

it('lists products for a store the authenticated user belongs to', function () {
    [$store, $user] = seedAdminContext();
    Product::factory()->count(3)->create(['store_id' => $store->getKey()]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson("/api/admin/v1/stores/{$store->getKey()}/products")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('rejects access to stores the user does not belong to', function () {
    [$store, $user] = seedAdminContext();
    $otherStore = Store::factory()->create();

    Sanctum::actingAs($user, ['*']);

    $this->getJson("/api/admin/v1/stores/{$otherStore->getKey()}/products")
        ->assertForbidden();
});

it('creates a product with write-products ability', function () {
    [$store, $user] = seedAdminContext();

    Sanctum::actingAs($user, ['write-products']);

    $response = $this->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [
        'title' => 'API Tee',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'API Tee');
});

it('rejects product creation when token lacks write-products ability', function () {
    [$store, $user] = seedAdminContext();

    Sanctum::actingAs($user, ['read-products']);

    $this->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [
        'title' => 'Denied',
    ])->assertForbidden();
});

it('validates required title on create', function () {
    [$store, $user] = seedAdminContext();

    Sanctum::actingAs($user, ['*']);

    $this->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

it('updates a product', function () {
    [$store, $user] = seedAdminContext();
    $product = Product::factory()->create(['store_id' => $store->getKey(), 'title' => 'Old']);

    Sanctum::actingAs($user, ['*']);

    $this->putJson("/api/admin/v1/stores/{$store->getKey()}/products/{$product->getKey()}", [
        'title' => 'New',
    ])->assertOk()->assertJsonPath('data.title', 'New');
});
