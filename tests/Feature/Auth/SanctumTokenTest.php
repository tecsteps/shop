<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Route::middleware('auth:sanctum')->get('/api/test/protected', function () {
        return response()->json(['ok' => true]);
    });

    Route::middleware(['auth:sanctum', 'ability:write-products'])->get('/api/test/write', function () {
        return response()->json(['ok' => true]);
    });
});

it('creates a personal access token with abilities', function () {
    $context = createStoreContext();

    $token = $context['user']->createToken('test-token', ['read-products', 'write-products']);

    expect($token->plainTextToken)->not->toBeEmpty();
    expect($token->accessToken->abilities)->toBe(['read-products', 'write-products']);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $context['user']->id,
        'name' => 'test-token',
    ]);
});

it('authenticates API request with valid token', function () {
    $context = createStoreContext();

    Sanctum::actingAs($context['user'], ['*']);

    $response = $this->getJson('/api/test/protected');

    $response->assertOk();
});

it('rejects API request with invalid token', function () {
    $response = $this->getJson('/api/test/protected', [
        'Authorization' => 'Bearer invalid-token-here',
    ]);

    $response->assertUnauthorized();
});

it('enforces token abilities', function () {
    $context = createStoreContext();

    $token = $context['user']->createToken('limited-token', ['read-products']);

    expect($token->accessToken->can('read-products'))->toBeTrue();
    expect($token->accessToken->can('write-products'))->toBeFalse();
});

it('revokes a token', function () {
    $context = createStoreContext();

    $token = $context['user']->createToken('revocable-token', ['*']);
    $tokenId = $token->accessToken->id;

    $context['user']->tokens()->where('id', $tokenId)->delete();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId,
    ]);
});
