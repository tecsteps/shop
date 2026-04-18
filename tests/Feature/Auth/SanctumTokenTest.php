<?php

use Laravel\Sanctum\Sanctum;

it('authenticates API requests with a valid token', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'sanctum-a.test']);
    Sanctum::actingAs($ctx['owner'], ['read-admin']);

    $this->getJson('http://sanctum-a.test/api/admin/v1/health')
        ->assertOk()
        ->assertJson(['ok' => true]);
});

it('rejects API requests without a token', function (): void {
    $this->createStoreContext(['hostname' => 'sanctum-b.test']);
    $this->getJson('http://sanctum-b.test/api/admin/v1/health')
        ->assertUnauthorized();
});

it('creates a personal access token with abilities', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'sanctum-c.test']);

    $token = $ctx['owner']->createToken('cli', ['read-products', 'write-products']);

    expect($token->accessToken->exists)->toBeTrue();
    expect($token->accessToken->abilities)->toEqual(['read-products', 'write-products']);
});
