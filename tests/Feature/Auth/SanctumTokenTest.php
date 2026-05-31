<?php

use App\Models\Product;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];

    Product::factory()->active()->create(['store_id' => $this->store->id]);
});

/**
 * Build the admin products index URL for the current store.
 */
function productsApiUrl(int $storeId): string
{
    return "/api/admin/v1/stores/{$storeId}/products";
}

it('creates a personal access token with abilities', function () {
    $newToken = $this->owner->createToken('cli', ['read-products', 'write-products']);

    expect($newToken->plainTextToken)->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $this->owner->id,
        'name' => 'cli',
    ]);

    expect($newToken->accessToken->abilities)->toContain('read-products', 'write-products');
});

it('authenticates API request with valid token', function () {
    $token = $this->owner->createToken('cli', ['read-products'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(productsApiUrl($this->store->id))
        ->assertSuccessful();
});

it('rejects API request with invalid token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson(productsApiUrl($this->store->id))
        ->assertUnauthorized();
});

it('enforces token abilities', function () {
    $token = $this->owner->createToken('cli', ['read-products'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(productsApiUrl($this->store->id), [
            'title' => 'New Product',
            'variants' => [['sku' => 'SKU-1', 'price_amount' => 1000, 'is_default' => true]],
        ])
        ->assertForbidden();
});

it('revokes a token', function () {
    $newToken = $this->owner->createToken('cli', ['read-products']);
    $plain = $newToken->plainTextToken;

    $newToken->accessToken->delete();

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson(productsApiUrl($this->store->id))
        ->assertUnauthorized();
});
