<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

function adminApiToken(User $user, array $abilities, ?\DateTimeInterface $expiresAt = null): string
{
    $plainTextToken = Str::random(48);
    $user->tokens()->create([
        'name' => 'acceptance-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
        'expires_at' => $expiresAt,
    ]);

    return $plainTextToken;
}

it('returns 401 when the admin API request has no valid token', function () {
    $store = shopStore();

    $this->getJson("/api/admin/v1/stores/{$store->id}/products")
        ->assertUnauthorized();
});

it('allows product reads for a matching ability and rejects writes without it', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    Product::factory()->create(['store_id' => $store->id]);
    $token = adminApiToken($user, ['read-products']);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/products")
        ->assertOk()
        ->assertJsonPath('total', 1);

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/products", [
        'title' => 'Not created',
        'variants' => [['price_amount' => 1000]],
    ])->assertForbidden();

    expect(Product::query()->where('title', 'Not created')->exists())->toBeFalse();
});

it('rejects expired tokens and users without membership in the requested store', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $expiredToken = adminApiToken($user, ['read-products'], now()->subMinute());

    $this->withToken($expiredToken)->getJson("/api/admin/v1/stores/{$store->id}/products")
        ->assertUnauthorized();

    $activeToken = adminApiToken($user, ['read-products']);
    $this->withToken($activeToken)->getJson("/api/admin/v1/stores/{$store->id}/products")
        ->assertForbidden();
});
