<?php

use App\Enums\StoreUserRole;
use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Developers\Index;
use App\Models\StoreUser;
use App\Models\User;
use App\Models\WebhookSubscription;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('renders the developers page', function () {
    $this->get(route('admin.developers.index'))->assertOk();
});

it('generates and revokes api tokens', function () {
    Livewire::test(Index::class)
        ->set('newTokenName', 'CI Token')
        ->set('newTokenAbilities', ['read-products'])
        ->call('generateToken')
        ->assertSet('generatedToken', fn ($value) => is_string($value) && $value !== '');

    $token = $this->user->tokens()->where('name', 'CI Token')->firstOrFail();

    expect($token->abilities)->toBe(['read-products']);

    Livewire::test(Index::class)
        ->call('revokeToken', $token->getKey())
        ->assertDispatched('toast');

    expect($this->user->tokens()->whereKey($token->getKey())->exists())->toBeFalse();
});

it('creates a webhook subscription', function () {
    Livewire::test(Index::class)
        ->call('openWebhookModal')
        ->set('webhookEventType', 'order.created')
        ->set('webhookUrl', 'https://example.com/hooks')
        ->call('saveWebhook')
        ->assertDispatched('toast');

    $subscription = WebhookSubscription::query()
        ->where('store_id', $this->store->getKey())
        ->where('event_type', 'order.created')
        ->firstOrFail();

    expect($subscription->target_url)->toBe('https://example.com/hooks')
        ->and($subscription->status)->toBe(WebhookSubscriptionStatus::Active);
});

it('pauses and deletes webhook subscriptions', function () {
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->getKey(),
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    Livewire::test(Index::class)
        ->call('toggleWebhookStatus', $subscription->getKey())
        ->assertDispatched('toast');

    expect($subscription->refresh()->status)->toBe(WebhookSubscriptionStatus::Paused);

    Livewire::test(Index::class)
        ->call('deleteWebhook', $subscription->getKey())
        ->assertDispatched('toast');

    expect(WebhookSubscription::query()->whereKey($subscription->getKey())->exists())->toBeFalse();
});

it('only lists webhooks from the current store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    WebhookSubscription::factory()->create([
        'store_id' => $otherStore->getKey(),
        'target_url' => 'https://other-store.example.com/hooks',
    ]);
    $this->actingAsAdmin($this->user, $this->store);

    Livewire::test(Index::class)->assertDontSee('other-store.example.com');
});

it('denies staff access to the developers page', function () {
    $staff = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $this->store->getKey(),
        'user_id' => $staff->getKey(),
        'role' => StoreUserRole::Staff,
    ]);
    $this->actingAsAdmin($staff, $this->store);

    $this->get(route('admin.developers.index'))->assertForbidden();
});
