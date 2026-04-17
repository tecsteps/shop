<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedAdminUserForStore(Store $store, string $role = 'owner'): User
{
    $user = User::factory()->create();
    \DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role,
        'created_at' => now(),
    ]);

    return $user;
}

it('creates a webhook subscription via the API', function () {
    $store = Store::factory()->create();
    $user = seedAdminUserForStore($store, StoreUserRole::Owner->value);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/admin/v1/stores/{$store->getKey()}/webhook-subscriptions", [
        'event_type' => 'order.paid',
        'target_url' => 'https://example.test/webhook',
        'signing_secret' => 'mysecret-1234',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.event_type', 'order.paid');

    expect(WebhookSubscription::query()->where('store_id', $store->getKey())->count())->toBe(1);
});

it('validates event_type', function () {
    $store = Store::factory()->create();
    $user = seedAdminUserForStore($store);

    Sanctum::actingAs($user, ['*']);

    $this->postJson("/api/admin/v1/stores/{$store->getKey()}/webhook-subscriptions", [
        'event_type' => 'nonsense',
        'target_url' => 'https://example.test/x',
        'signing_secret' => 'shortsec',
    ])->assertStatus(422)->assertJsonValidationErrors(['event_type']);
});
