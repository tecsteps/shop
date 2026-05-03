<?php

use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->organization = Organization::query()->where('billing_email', 'billing@acme.test')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminPlatformTokenFor($test, array $abilities, ?User $user = null): string
{
    return app(ApiTokenService::class)->create($test->store, $user ?? $test->user, 'Platform API test', $abilities)['plain_text_token'];
}

test('platform api creates organizations and stores for owner manage-platform tokens', function (): void {
    $organizationUrl = route('api.admin.platform.organizations.store');

    $this->postJson($organizationUrl)->assertUnauthorized();

    $this->withToken(adminPlatformTokenFor($this, ['read-orders']))
        ->postJson($organizationUrl, [
            'name' => 'API Holdings',
            'billing_email' => 'billing@api.test',
        ])
        ->assertForbidden();

    $organizationResponse = $this->withToken(adminPlatformTokenFor($this, ['manage-platform']))
        ->postJson($organizationUrl, [
            'name' => 'API Holdings',
            'billing_email' => 'billing@api.test',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'API Holdings')
        ->assertJsonPath('data.billing_email', 'billing@api.test');

    $storeResponse = $this->withToken(adminPlatformTokenFor($this, ['manage-platform']))
        ->postJson(route('api.admin.platform.stores.store'), [
            'organization_id' => $organizationResponse->json('data.id'),
            'name' => 'API Flagship',
            'handle' => 'api-flagship',
            'default_currency' => 'eur',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'API Flagship')
        ->assertJsonPath('data.handle', 'api-flagship')
        ->assertJsonPath('data.default_currency', 'EUR');

    $store = Store::query()->whereKey($storeResponse->json('data.id'))->firstOrFail();

    expect($store->organization_id)->toBe($organizationResponse->json('data.id'))
        ->and(StoreUser::query()
            ->where('store_id', $store->id)
            ->where('user_id', $this->user->id)
            ->where('role', StoreUserRole::Owner->value)
            ->exists())->toBeTrue();
});

test('platform api validates store creation payloads', function (): void {
    $this->withToken(adminPlatformTokenFor($this, ['manage-platform']))
        ->postJson(route('api.admin.platform.stores.store'), [
            'organization_id' => $this->organization->id,
            'name' => 'Broken Store',
            'handle' => 'Broken Store',
            'default_currency' => 'EURO',
            'default_locale' => 'english',
            'timezone' => 'Mars/Base',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['handle', 'default_currency', 'default_locale', 'timezone']);
});

test('platform api invites users and reports current token membership', function (): void {
    $memberToken = adminPlatformTokenFor($this, ['read-orders']);

    $this->withToken($memberToken)
        ->getJson(route('api.admin.stores.me', $this->store))
        ->assertOk()
        ->assertJsonPath('data.user_id', $this->user->id)
        ->assertJsonPath('data.store_id', $this->store->id)
        ->assertJsonPath('data.role', StoreUserRole::Owner->value)
        ->assertJsonPath('data.permissions.0', 'read-orders');

    $inviteUrl = route('api.admin.stores.invites.store', $this->store);

    $this->withToken(adminPlatformTokenFor($this, ['manage-platform']))
        ->postJson($inviteUrl, [
            'email' => 'new.staff@example.test',
            'role' => StoreUserRole::Staff->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'new.staff@example.test')
        ->assertJsonPath('data.role', StoreUserRole::Staff->value);

    $invitedUser = User::query()->where('email', 'new.staff@example.test')->firstOrFail();

    expect(StoreUser::query()
        ->where('store_id', $this->store->id)
        ->where('user_id', $invitedUser->id)
        ->where('role', StoreUserRole::Staff->value)
        ->exists())->toBeTrue();

    $this->withToken(adminPlatformTokenFor($this, ['manage-platform']))
        ->postJson($inviteUrl, [
            'email' => 'new.staff@example.test',
            'role' => StoreUserRole::Staff->value,
        ])
        ->assertConflict();

    $this->withToken(adminPlatformTokenFor($this, ['manage-platform']))
        ->postJson(route('api.admin.stores.invites.store', $this->otherStore), [
            'email' => 'other.staff@example.test',
            'role' => StoreUserRole::Staff->value,
        ])
        ->assertForbidden();
});

test('manage-platform tokens require an owner token user', function (): void {
    $staff = User::factory()->create(['email' => 'staff-owner-check@example.test']);
    StoreUser::query()->create([
        'store_id' => $this->store->id,
        'user_id' => $staff->id,
        'role' => StoreUserRole::Staff->value,
    ]);

    $this->withToken(adminPlatformTokenFor($this, ['manage-platform'], $staff))
        ->postJson(route('api.admin.platform.organizations.store'), [
            'name' => 'Forbidden Holdings',
            'billing_email' => 'forbidden@example.test',
        ])
        ->assertForbidden();
});
