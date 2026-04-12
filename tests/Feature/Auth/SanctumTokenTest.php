<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to issue an API token', function (): void {
    $user = User::factory()->create();

    $token = $user->createToken('test-token');

    expect($token->plainTextToken)->toBeString()
        ->and($user->tokens()->count())->toBe(1);
});

it('authenticates an API request with a valid bearer token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/admin/user');

    $response->assertOk()
        ->assertJsonPath('id', $user->id);
});

it('rejects an API request without a token', function (): void {
    $response = $this->getJson('/api/admin/user');

    $response->assertStatus(401);
});
