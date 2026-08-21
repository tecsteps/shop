<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();
});

test('admin bearer tokens can read only the abilities they were granted', function (): void {
    $token = $this->admin->createToken('catalog-reader', ['read-products'])->plainTextToken;

    $this->withToken($token)
        ->getJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/products")
        ->assertOk();

    $this->withToken($token)
        ->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/collections", ['title' => 'Blocked', 'type' => 'manual'])
        ->assertForbidden();
});

test('admin bearer tokens can write with the matching ability', function (): void {
    $token = $this->admin->createToken('catalog-manager', ['write-collections'])->plainTextToken;

    $this->withToken($token)
        ->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/collections", ['title' => 'Token Collection', 'type' => 'manual'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Token Collection');
});

test('session-authenticated admins cannot use the admin API', function (): void {
    $this->actingAs($this->admin)
        ->getJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/products")
        ->assertUnauthorized();
});

test('admin bearer tokens cannot access stores where the user is not a member', function (): void {
    $otherStore = Store::factory()->create();
    $token = $this->admin->createToken('foreign-store-reader', ['read-products'])->plainTextToken;

    $this->withToken($token)
        ->getJson("http://shop.test/api/admin/v1/stores/{$otherStore->getKey()}/products")
        ->assertForbidden();
});

test('platform management is restricted to platform administrators', function (): void {
    $member = User::factory()->create();
    $member->stores()->attach($this->store, ['role' => 'admin']);
    $platformToken = $this->admin->createToken('platform-manager', ['manage-platform'])->plainTextToken;

    $this->withToken($platformToken)
        ->postJson('http://shop.test/api/admin/v1/platform/organizations', ['name' => 'Allowed Platform Org', 'billing_email' => 'allowed@example.test'])
        ->assertCreated();
});

test('non-platform admins cannot use platform management even with the ability', function (): void {
    $member = User::factory()->create();
    $member->stores()->attach($this->store, ['role' => 'admin']);
    $token = $member->createToken('platform-attempt', ['manage-platform'])->plainTextToken;

    $this->withToken($token)
        ->postJson('http://shop.test/api/admin/v1/platform/organizations', ['name' => 'Blocked Platform Org', 'billing_email' => 'blocked@example.test'])
        ->assertForbidden();
});
