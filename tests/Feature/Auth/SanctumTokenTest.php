<?php

use Laravel\Sanctum\Sanctum;

it('creates a personal access token with abilities', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    $token = $user->createToken('test-token', ['read-products', 'write-products']);

    expect($token->plainTextToken)->not->toBeEmpty();
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'test-token',
    ]);
});

it('authenticates API request with valid token', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    Sanctum::actingAs($user, ['read-products']);

    $response = $this->getJson('/api/admin/v1/stores/'.$ctx['store']->id.'/products');

    $response->assertStatus(200);
});

it('rejects API request with invalid token', function () {
    $ctx = createStoreContext();

    $response = $this->getJson('/api/admin/v1/stores/'.$ctx['store']->id.'/products', [
        'Authorization' => 'Bearer fake-token',
    ]);

    $response->assertStatus(401);
});

it('enforces token abilities', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    Sanctum::actingAs($user, ['read-products']);

    $response = $this->postJson('/api/admin/v1/stores/'.$ctx['store']->id.'/products', [
        'title' => 'Test Product',
    ]);

    $response->assertStatus(403);
});

it('revokes a token', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    $token = $user->createToken('test-token', ['read-products']);
    $tokenId = $token->accessToken->id;

    $user->tokens()->where('id', $tokenId)->delete();

    $response = $this->getJson('/api/admin/v1/stores/'.$ctx['store']->id.'/products', [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ]);

    $response->assertStatus(401);
});
