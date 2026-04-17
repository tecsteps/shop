<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Settings\Webhooks\Deliveries as AdminWebhookDeliveries;
use App\Livewire\Admin\Settings\Webhooks\Edit as AdminWebhookEdit;
use App\Livewire\Admin\Settings\Webhooks\Index as AdminWebhooksIndex;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function asOwner(Store $store): User
{
    $user = User::factory()->create();
    \DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    return $user;
}

it('renders the webhooks index page', function () {
    $store = Store::factory()->create();
    $user = asOwner($store);
    app()->instance('current_store', $store);

    $this->actingAs($user);

    Livewire::test(AdminWebhooksIndex::class)
        ->assertStatus(200)
        ->assertSee('Webhooks');
});

it('saves a new webhook subscription', function () {
    $store = Store::factory()->create();
    $user = asOwner($store);
    app()->instance('current_store', $store);

    $this->actingAs($user);

    Livewire::test(AdminWebhookEdit::class)
        ->set('event_type', 'order.paid')
        ->set('target_url', 'https://example.test/hook')
        ->set('signing_secret', 'mysecret-12345')
        ->set('status', 'active')
        ->call('save');

    $subscription = WebhookSubscription::query()->where('store_id', $store->getKey())->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->target_url)->toBe('https://example.test/hook');
});

it('renders the deliveries page', function () {
    $store = Store::factory()->create();
    $user = asOwner($store);
    app()->instance('current_store', $store);

    $this->actingAs($user);

    $subscription = WebhookSubscription::factory()->create(['store_id' => $store->getKey()]);

    Livewire::test(AdminWebhookDeliveries::class, ['subscription' => (int) $subscription->getKey()])
        ->assertStatus(200)
        ->assertSee('Deliveries');
});
