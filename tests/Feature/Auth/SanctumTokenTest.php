<?php

use App\Models\Product;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('creates a personal access token with abilities', function () {
    $token = $this->user->createToken('api-token', ['read-products', 'write-products']);

    expect($token->plainTextToken)->not->toBeEmpty();
    expect($token->accessToken->abilities)->toBe(['read-products', 'write-products']);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $this->user->id,
        'name' => 'api-token',
    ]);
});

it('authenticates API request with valid token', function () {
    Product::factory()->active()->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertStatus(200);
});

it('rejects API request with invalid token', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token-here'])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertStatus(401);
});

it('enforces token abilities', function () {
    $token = $this->user->createToken('test', ['read-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'New Product',
        ]);

    $response->assertStatus(403);
});

it('revokes a token', function () {
    $tokenResult = $this->user->createToken('test', ['read-products']);

    // Verify token exists
    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $tokenResult->accessToken->id,
        'tokenable_id' => $this->user->id,
    ]);

    // Revoke
    $tokenResult->accessToken->delete();

    // Verify token is removed from database
    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenResult->accessToken->id,
    ]);

    expect($this->user->tokens()->count())->toBe(0);
});
