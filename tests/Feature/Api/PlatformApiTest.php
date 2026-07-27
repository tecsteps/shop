<?php

use App\Models\Organization;
use App\Models\StoreUser;
use App\Models\User;
use App\Services\ApiTokenService;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->tokens = app(ApiTokenService::class);
    $this->token = $this->tokens->create($this->user, 'Platform', ['manage-platform', 'read-products']);
});

test('creates an organization', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.com',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Corp')
        ->assertJsonPath('data.billing_email', 'billing@acme.com');

    expect(Organization::query()->where('name', 'Acme Corp')->exists())->toBeTrue();
});

test('requires manage-platform ability for organizations', function () {
    $token = $this->tokens->create($this->user, 'Scoped', ['read-products']);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.com',
        ])
        ->assertForbidden();
});

test('creates a store within an organization', function () {
    $organization = Organization::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/admin/v1/platform/stores', [
            'organization_id' => $organization->id,
            'name' => 'Acme Store',
            'handle' => 'acme-'.fake()->unique()->numberBetween(1000, 9999),
            'default_currency' => 'eur',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Store')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.default_currency', 'EUR')
        ->assertJsonPath('data.timezone', 'Europe/Berlin');
});

test('validates the store handle format', function () {
    $organization = Organization::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/admin/v1/platform/stores', [
            'organization_id' => $organization->id,
            'name' => 'Acme Store',
            'handle' => 'Invalid_Handle',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['handle']);
});

test('invites a new user to the store', function () {
    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/invites", [
            'email' => 'staff@acme.com',
            'role' => 'staff',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'staff@acme.com')
        ->assertJsonPath('data.role', 'staff');

    $user = User::query()->where('email', 'staff@acme.com')->sole();

    expect(StoreUser::query()
        ->where('store_id', $this->store->id)
        ->where('user_id', $user->id)
        ->where('role', 'staff')
        ->exists())->toBeTrue();
});

test('invites an existing user who is not yet a member', function () {
    $existing = User::factory()->create(['email' => 'existing@acme.com']);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/invites", [
            'email' => 'existing@acme.com',
            'role' => 'support',
        ])
        ->assertCreated();

    expect(StoreUser::query()
        ->where('store_id', $this->store->id)
        ->where('user_id', $existing->id)
        ->where('role', 'support')
        ->exists())->toBeTrue();
});

test('returns 409 when the user is already a member', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/invites", [
            'email' => $this->user->email,
            'role' => 'staff',
        ])
        ->assertConflict();
});

test('returns the current user membership details', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/me")
        ->assertOk()
        ->assertJsonPath('data.user_id', $this->user->id)
        ->assertJsonPath('data.store_id', $this->store->id)
        ->assertJsonPath('data.role', 'owner')
        ->assertJsonPath('data.email', $this->user->email)
        ->assertJsonPath('data.permissions', ['manage-platform', 'read-products']);
});

test('returns 403 for a store the user is not a member of', function () {
    $otherStore = $this->createStore();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$otherStore->id}/me")
        ->assertForbidden();
});

test('returns 404 for a nonexistent store', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson('/api/admin/v1/stores/999999/me')
        ->assertNotFound();
});

test('requires authentication for platform endpoints', function () {
    $this->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Acme Corp',
        'billing_email' => 'billing@acme.com',
    ])->assertUnauthorized();
});
