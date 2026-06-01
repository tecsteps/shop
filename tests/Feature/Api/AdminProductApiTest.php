<?php

use App\Models\Product;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

/**
 * Authenticate the owner with a Sanctum token carrying the given abilities.
 *
 * @param  list<string>  $abilities
 */
function actingWithToken(\App\Models\User $user, array $abilities): void
{
    Sanctum::actingAs($user, $abilities);
}

function adminProductsUrl(int $storeId, string $suffix = ''): string
{
    return "/api/admin/v1/stores/{$storeId}/products".$suffix;
}

it('lists products with authentication', function () {
    Product::factory()->count(3)->active()->create(['store_id' => $this->store->id]);

    actingWithToken($this->owner, ['read-products']);

    $this->getJson(adminProductsUrl($this->store->id))
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
        ->assertJsonCount(3, 'data');
});

it('creates a product via API', function () {
    actingWithToken($this->owner, ['write-products']);

    $this->postJson(adminProductsUrl($this->store->id), [
        'title' => 'Classic T-Shirt',
        'status' => 'active',
        'variants' => [
            ['sku' => 'TSH-BLU-S', 'price_amount' => 2500, 'is_default' => true],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Classic T-Shirt');

    $this->assertDatabaseHas('products', [
        'store_id' => $this->store->id,
        'title' => 'Classic T-Shirt',
    ]);
});

it('updates a product via API', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Old']);

    actingWithToken($this->owner, ['write-products']);

    $this->putJson(adminProductsUrl($this->store->id, "/{$product->id}"), [
        'title' => 'Updated Title',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Updated Title');

    expect($product->fresh()->title)->toBe('Updated Title');
});

it('deletes a draft product via API', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    actingWithToken($this->owner, ['write-products']);

    $this->deleteJson(adminProductsUrl($this->store->id, "/{$product->id}"))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'archived');
});

it('requires write-products ability for mutations', function () {
    actingWithToken($this->owner, ['read-products']);

    $this->postJson(adminProductsUrl($this->store->id), [
        'title' => 'Nope',
        'variants' => [['sku' => 'X', 'price_amount' => 100, 'is_default' => true]],
    ])->assertForbidden();
});

it('returns 401 without token', function () {
    $this->getJson(adminProductsUrl($this->store->id))->assertUnauthorized();
});

it('paginates results', function () {
    Product::factory()->count(25)->active()->create(['store_id' => $this->store->id]);

    actingWithToken($this->owner, ['read-products']);

    $this->getJson(adminProductsUrl($this->store->id).'?per_page=15')
        ->assertSuccessful()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 25);
});
