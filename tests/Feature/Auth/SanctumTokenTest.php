<?php

use App\Services\ApiTokenService;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->tokens = app(ApiTokenService::class);
});

test('creates a personal access token with abilities', function () {
    $plain = $this->tokens->create($this->user, 'My integration', ['read-products', 'write-products']);

    expect($plain)->toStartWith('shop_')->toHaveLength(45);

    $token = PersonalAccessToken::query()->sole();

    expect($token->tokenable_id)->toBe($this->user->id)
        ->and($token->name)->toBe('My integration')
        ->and($token->abilities)->toBe(['read-products', 'write-products'])
        ->and($token->token)->toBe(hash('sha256', $plain))
        ->and($token->expires_at)->not->toBeNull();
});

test('token abilities are enforced by tokenCan', function () {
    $plain = $this->tokens->create($this->user, 'Scoped token', ['read-products']);

    $token = PersonalAccessToken::findToken($plain);

    expect($this->user->withAccessToken($token)->tokenCan('read-products'))->toBeTrue()
        ->and($this->user->withAccessToken($token)->tokenCan('write-products'))->toBeFalse();
});

test('authenticates API request with valid token', function () {
    $plain = $this->tokens->create($this->user, 'API token', ['read-products']);

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertOk();
});

test('rejects API request with invalid token', function () {
    $this->withHeader('Authorization', 'Bearer shop_not-a-real-token')
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertUnauthorized();
});

test('enforces token abilities on endpoints', function () {
    $plain = $this->tokens->create($this->user, 'Read-only token', ['read-products']);

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'Forbidden Product',
            'variants' => [['sku' => 'NOPE-1', 'price_amount' => 100]],
        ])
        ->assertForbidden();
});

test('revokes a token', function () {
    $plain = $this->tokens->create($this->user, 'Temporary token', ['read-products']);

    expect($this->tokens->revoke($this->user, PersonalAccessToken::query()->sole()->id))->toBeTrue();

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertUnauthorized();
});

test('rejects an expired token', function () {
    $plain = $this->tokens->create($this->user, 'Expired token', ['read-products'], now()->subDay());

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertUnauthorized();
});

test('does not revoke another user\'s token', function () {
    $other = $this->createUserWithRole($this->store, 'admin');
    $plain = $this->tokens->create($other, 'Other user token', ['read-products']);

    expect($this->tokens->revoke($this->user, PersonalAccessToken::query()->sole()->id))->toBeFalse();
    expect(PersonalAccessToken::findToken($plain))->not->toBeNull();
});
