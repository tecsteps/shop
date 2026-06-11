<?php

use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('creates a personal access token with abilities', function () {
    $token = $this->user->createToken('My integration', ['read-products', 'write-products']);

    expect($token->plainTextToken)->toContain('shop_');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $this->user->getKey(),
        'name' => 'My integration',
    ]);

    expect(PersonalAccessToken::query()->first()->abilities)
        ->toBe(['read-products', 'write-products']);
});

it('authenticates API request with valid token', function () {
    $token = $this->user->createToken('integration', ['read-products'])->plainTextToken;

    $this->getJson("/api/admin/v1/stores/{$this->store->getKey()}/products", [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk();
});

it('rejects API request with invalid token', function () {
    $this->getJson("/api/admin/v1/stores/{$this->store->getKey()}/products", [
        'Authorization' => 'Bearer shop_totally-fake-token',
    ])->assertUnauthorized();
});

it('enforces token abilities', function () {
    $token = $this->user->createToken('read only', ['read-products'])->plainTextToken;

    $this->postJson("/api/admin/v1/stores/{$this->store->getKey()}/products", [
        'title' => 'New Product',
        'variants' => [['sku' => 'NP-1', 'price_amount' => 1000]],
    ], [
        'Authorization' => 'Bearer '.$token,
    ])->assertForbidden();
});

it('revokes a token', function () {
    $token = $this->user->createToken('revocable', ['read-products']);

    $this->user->tokens()->whereKey($token->accessToken->getKey())->delete();

    $this->getJson("/api/admin/v1/stores/{$this->store->getKey()}/products", [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->assertUnauthorized();
});
