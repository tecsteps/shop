<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;

function adminApiToken(User $user, ?Store $store, array $abilities, ?\DateTimeInterface $expiresAt = null): string
{
    $plainTextToken = Str::random(48);
    $user->tokens()->create([
        'store_id' => $store?->id,
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
    $token = adminApiToken($user, $store, ['read-products']);

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
    $expiredToken = adminApiToken($user, $store, ['read-products'], now()->subMinute());

    $this->withToken($expiredToken)->getJson("/api/admin/v1/stores/{$store->id}/products")
        ->assertUnauthorized();

    $activeToken = adminApiToken($user, $store, ['read-products']);
    $this->withToken($activeToken)->getJson("/api/admin/v1/stores/{$store->id}/products")
        ->assertForbidden();
});

it('rejects a store token when its owner requests another store', function () {
    $firstStore = shopStore();
    $secondStore = shopStore('api-token-second.test');
    $user = User::factory()->create();
    $user->stores()->attach([$firstStore->id => ['role' => 'owner'], $secondStore->id => ['role' => 'owner']]);
    $token = adminApiToken($user, $firstStore, ['read-products']);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$firstStore->id}/products")
        ->assertOk();

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$secondStore->id}/products")
        ->assertForbidden()
        ->assertJsonPath('message', 'This token is not authorized for the requested store.');
});

it('allows an explicit platform token to reach member stores', function () {
    $firstStore = shopStore();
    $secondStore = shopStore('platform-token-second.test');
    $user = User::factory()->create();
    $user->stores()->attach([$firstStore->id => ['role' => 'owner'], $secondStore->id => ['role' => 'owner']]);
    $token = adminApiToken($user, null, ['manage-platform', 'read-products']);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$firstStore->id}/products")
        ->assertOk();

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$secondStore->id}/products")
        ->assertOk();
});
