<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreInvitation;
use App\Models\User;
use Illuminate\Support\Str;

function platformManagementToken(User $user, ?Store $store, array $abilities): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $store?->id,
        'name' => 'platform-management-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

it('creates organizations and stores through platform-scoped endpoints', function () {
    $user = User::factory()->create();
    $token = platformManagementToken($user, null, ['manage-platform']);

    $organizationResponse = $this->withToken($token)->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Acme Corporation',
        'billing_email' => 'billing@acme.test',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Acme Corporation')
        ->assertJsonPath('data.billing_email', 'billing@acme.test');

    $organizationId = $organizationResponse->json('data.id');
    $storeResponse = $this->postJson('/api/admin/v1/platform/stores', [
        'organization_id' => $organizationId,
        'name' => 'Acme Store',
        'handle' => 'acme-store',
        'default_currency' => 'EUR',
        'default_locale' => 'de',
        'timezone' => 'Europe/Berlin',
    ])->assertCreated()
        ->assertJsonPath('data.organization_id', $organizationId)
        ->assertJsonPath('data.handle', 'acme-store')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.default_currency', 'EUR');

    $storeId = $storeResponse->json('data.id');
    $this->assertDatabaseHas('stores', ['organization_id' => $organizationId, 'handle' => 'acme-store', 'status' => 'active']);
    $this->assertDatabaseHas('store_users', ['store_id' => $storeId, 'user_id' => $user->id, 'role' => 'owner']);
    expect(Store::query()->findOrFail($storeId)->users()->wherePivot('role', 'owner')->count())->toBe(1);
    expect(Organization::query()->count())->toBe(1);
});

it('rejects missing or store-bound platform authority', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    $user = User::factory()->create();

    $this->postJson('/api/admin/v1/platform/organizations', ['name' => 'No token'])->assertUnauthorized();

    $readOnlyToken = platformManagementToken($user, null, ['read-products']);
    $this->withToken($readOnlyToken)->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'No scope',
        'billing_email' => 'scope@acme.test',
    ])->assertForbidden();

    $storeToken = platformManagementToken($user, $store, ['manage-platform']);
    $this->withToken($storeToken)->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Store-bound token',
        'billing_email' => 'bound@acme.test',
    ])->assertForbidden();

    expect(Organization::query()->where('name', 'Store-bound token')->exists())->toBeFalse();
});

it('validates store handles locale currency timezone and organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $token = platformManagementToken($user, null, ['manage-platform']);

    $this->withToken($token)->postJson('/api/admin/v1/platform/stores', [
        'organization_id' => $organization->id,
        'name' => 'Invalid store',
        'handle' => 'Bad_Handle',
        'default_currency' => 'ZZZ',
        'default_locale' => 'zz',
        'timezone' => 'Not/A_Timezone',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['handle', 'default_currency', 'default_locale', 'timezone']);

    $this->withToken($token)->postJson('/api/admin/v1/platform/stores', [
        'organization_id' => 999999,
        'name' => 'Unknown parent',
        'handle' => 'unknown-parent',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ])->assertUnprocessable()->assertJsonValidationErrors('organization_id');

    expect(Store::query()->where('name', 'Invalid store')->exists())->toBeFalse();
});

it('persists store invitations and rejects existing store members', function () {
    $store = shopStore();
    $invitedUser = User::factory()->create(['email' => 'staff@acme.test']);
    $operator = User::factory()->create();
    $token = platformManagementToken($operator, null, ['manage-platform']);

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/invites", [
        'email' => 'invitee@acme.test',
        'role' => 'staff',
    ])->assertCreated()
        ->assertJsonPath('data.email', 'invitee@acme.test')
        ->assertJsonPath('data.role', 'staff');

    $invitation = StoreInvitation::query()->firstOrFail();
    expect($invitation->store_id)->toBe($store->id)
        ->and($invitation->token_hash)->toHaveLength(64)
        ->and($invitation->token_hash)->not->toBe('');

    $store->users()->attach($invitedUser->id, ['role' => 'staff']);
    $this->postJson("/api/admin/v1/stores/{$store->id}/invites", [
        'email' => 'STAFF@ACME.TEST',
        'role' => 'admin',
    ])->assertStatus(409);

    expect(StoreInvitation::query()->count())->toBe(1);
});

it('limits the admin API per bearer token instead of sharing a client IP bucket', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $firstToken = platformManagementToken($firstUser, null, ['manage-platform']);
    $secondToken = platformManagementToken($secondUser, null, ['manage-platform']);

    for ($requestNumber = 1; $requestNumber <= 60; $requestNumber++) {
        $this->withToken($firstToken)->postJson('/api/admin/v1/platform/organizations', [
            'name' => "Rate limited organization {$requestNumber}",
            'billing_email' => "billing-{$requestNumber}@acme.test",
        ])->assertCreated();
    }

    $this->withToken($firstToken)->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Over limit organization',
        'billing_email' => 'over-limit@acme.test',
    ])->assertStatus(429);

    $this->withToken($secondToken)->postJson('/api/admin/v1/platform/organizations', [
        'name' => 'Second token organization',
        'billing_email' => 'second-token@acme.test',
    ])->assertCreated();
});

it('validates invitation payload and does not expose the secret token hash', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $token = platformManagementToken($user, null, ['manage-platform']);

    $response = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/invites", [
        'email' => 'not-an-email',
        'role' => 'superuser',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'role']);

    expect($response->json())->not->toHaveKey('token_hash')
        ->and(StoreInvitation::query()->count())->toBe(0);
});
