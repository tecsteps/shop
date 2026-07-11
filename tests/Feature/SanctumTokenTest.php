<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('users can create Sanctum API tokens with abilities', function () {
    $user = User::factory()->create();

    $newAccessToken = $user->createToken('test-client', ['orders:read']);
    $storedAccessToken = $user->tokens()->sole();

    expect($newAccessToken->plainTextToken)
        ->toStartWith($storedAccessToken->getKey().'|')
        ->and($storedAccessToken->can('orders:read'))->toBeTrue()
        ->and($storedAccessToken->can('orders:write'))->toBeFalse();
});
