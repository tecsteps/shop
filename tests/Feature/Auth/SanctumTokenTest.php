<?php

// Sanctum is not installed yet. These tests will be enabled once
// laravel/sanctum is added and the HasApiTokens trait is applied to User.

beforeEach(function (): void {
    if (! trait_exists('Laravel\\Sanctum\\HasApiTokens')) {
        $this->markTestSkipped('Laravel Sanctum is not installed.');
    }
});

it('creates personal access token with abilities', function (): void {
    $ctx = createStoreContext();

    $this->actingAs($ctx['user']);

    $token = $ctx['user']->createToken('test-token', ['read-products', 'write-products']);

    expect($token->plainTextToken)->not->toBeEmpty();
    expect($token->accessToken->abilities)->toBe(['read-products', 'write-products']);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $ctx['user']->id,
        'name' => 'test-token',
    ]);
});

it('authenticates API request with valid token', function (): void {
    $ctx = createStoreContext();

    $token = $ctx['user']->createToken('api-token', ['*']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson('/api/admin/v1');

    $response->assertOk();
})->skip('No admin API routes defined yet');

it('rejects API request with invalid token', function (): void {
    createStoreContext();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer fake-invalid-token',
    ])->getJson('/api/admin/v1');

    $response->assertUnauthorized();
})->skip('No admin API routes defined yet');

it('enforces token abilities', function (): void {
    $ctx = createStoreContext();

    $token = $ctx['user']->createToken('limited-token', ['read-products']);

    expect($token->accessToken->can('read-products'))->toBeTrue();
    expect($token->accessToken->can('write-products'))->toBeFalse();
    expect($token->accessToken->cant('write-products'))->toBeTrue();
});

it('revokes a token', function (): void {
    $ctx = createStoreContext();

    $token = $ctx['user']->createToken('revoke-test', ['*']);
    $tokenId = $token->accessToken->id;

    $ctx['user']->tokens()->where('id', $tokenId)->delete();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId,
    ]);
});
