<?php

use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\StoreUser;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a personal access token with abilities', function () {
    ['user' => $user] = $this->createStoreContext();

    $response = $this->actingAs($user)->postJson('/api/admin/v1/tokens', [
        'name' => 'cli',
        'abilities' => ['read-products', 'write-products'],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.name', 'cli');
    $response->assertJsonPath('data.abilities', ['read-products', 'write-products']);
    expect($response->json('data.token'))->toMatch('/^\d+\|shop_/');

    $token = PersonalAccessToken::query()->where('tokenable_id', $user->id)->first();
    expect($token)->not->toBeNull();
    expect($token->abilities)->toBe(['read-products', 'write-products']);
});

it('authenticates api requests with a valid token', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('cli', ['read-products'])->plainTextToken;

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/me", [
        'Authorization' => "Bearer {$plainText}",
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.role', 'owner');
    $response->assertJsonPath('data.store_id', $store->id);
    $response->assertJsonPath('data.user_id', $user->id);
});

it('rejects api requests with an invalid token', function () {
    ['store' => $store] = $this->createStoreContext();

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/me", [
        'Authorization' => 'Bearer shop_invalidtokenvalue1234567890',
    ]);

    $response->assertUnauthorized();
});

it('enforces token abilities', function () {
    ['user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('readonly', ['read-products'])->plainTextToken;

    $response = $this->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Acme Corp',
        'billing_email' => 'billing@acme.com',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertForbidden();
});

it('revokes a token', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('cli')->plainTextToken;
    $tokenId = $user->tokens()->first()->id;

    $this->getJson("/api/admin/v1/stores/{$store->id}/me", [
        'Authorization' => "Bearer {$plainText}",
    ])->assertOk();

    $this->actingAs($user)->deleteJson("/api/admin/v1/tokens/{$tokenId}")->assertNoContent();

    $this->app['auth']->forgetGuards();

    $this->getJson("/api/admin/v1/stores/{$store->id}/me", [
        'Authorization' => "Bearer {$plainText}",
    ])->assertUnauthorized();
});

it('creates an organization via the platform api', function () {
    ['user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('platform', ['manage-platform'])->plainTextToken;

    $response = $this->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Acme Corp',
        'billing_email' => 'billing@acme.com',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertCreated();
    $response->assertJsonPath('data.name', 'Acme Corp');
    $response->assertJsonPath('data.billing_email', 'billing@acme.com');
    expect(Organization::query()->where('name', 'Acme Corp')->exists())->toBeTrue();
});

it('creates a store via the platform api', function () {
    ['user' => $user] = $this->createStoreContext();
    $organization = Organization::factory()->create();
    $plainText = $user->createToken('platform', ['manage-platform'])->plainTextToken;

    $response = $this->postJson('/api/admin/v1/platform/stores', [
        'organization_id' => $organization->id,
        'name' => 'Acme Store',
        'handle' => 'acme',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'Europe/Berlin',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertCreated();
    $response->assertJsonPath('data.handle', 'acme');
    $response->assertJsonPath('data.status', 'active');
});

it('validates platform store creation', function () {
    ['user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('platform', ['manage-platform'])->plainTextToken;

    $response = $this->postJson('/api/admin/v1/platform/stores', [
        'organization_id' => 999999,
        'name' => '',
        'handle' => 'NOT VALID!',
        'default_currency' => 'EURO',
        'default_locale' => 'en',
        'timezone' => 'Mars/Olympus',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['organization_id', 'name', 'handle', 'default_currency', 'timezone']);
});

it('invites a user to a store', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $invitee = User::factory()->create();
    $plainText = $user->createToken('platform', ['manage-platform'])->plainTextToken;

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/invites", [
        'email' => $invitee->email,
        'role' => 'staff',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertCreated();
    $response->assertJsonPath('data.email', $invitee->email);
    $response->assertJsonPath('data.role', 'staff');
    expect($invitee->roleForStore($store))->toBe(StoreUserRole::Staff);
});

it('rejects duplicate invites with conflict', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $member = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id, 'user_id' => $member->id, 'role' => StoreUserRole::Staff,
    ]);
    $plainText = $user->createToken('platform', ['manage-platform'])->plainTextToken;

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/invites", [
        'email' => $member->email,
        'role' => 'staff',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertConflict();
});

it('rejects invites for unknown users', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('platform', ['manage-platform'])->plainTextToken;

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/invites", [
        'email' => 'ghost@example.com',
        'role' => 'staff',
    ], ['Authorization' => "Bearer {$plainText}"]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('denies membership details to non-members', function () {
    ['store' => $store] = $this->createStoreContext();
    $stranger = User::factory()->create();
    $plainText = $stranger->createToken('cli')->plainTextToken;

    $this->getJson("/api/admin/v1/stores/{$store->id}/me", [
        'Authorization' => "Bearer {$plainText}",
    ])->assertForbidden();
});

it('returns 404 for membership details of unknown stores', function () {
    ['user' => $user] = $this->createStoreContext();
    $plainText = $user->createToken('cli')->plainTextToken;

    $this->getJson('/api/admin/v1/stores/999999/me', [
        'Authorization' => "Bearer {$plainText}",
    ])->assertNotFound();
});

it('prevents non-owners from minting manage-platform tokens', function () {
    ['user' => $user] = $this->createStoreContext(role: StoreUserRole::Staff);

    $response = $this->actingAs($user)->postJson('/api/admin/v1/tokens', [
        'name' => 'escalation',
        'abilities' => ['manage-platform'],
    ]);

    $response->assertForbidden();
});

it('rejects unknown token abilities', function () {
    ['user' => $user] = $this->createStoreContext();

    $response = $this->actingAs($user)->postJson('/api/admin/v1/tokens', [
        'name' => 'cli',
        'abilities' => ['read-products', 'hack-everything'],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['abilities.1']);
});
