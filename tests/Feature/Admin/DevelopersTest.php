<?php

use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Developers\Index;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('renders the developers page', function () {
    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/developers')
        ->assertOk()
        ->assertSee('Developers')
        ->assertSee('API tokens')
        ->assertSee('Webhooks');
});

test('generates a token and shows it once', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('newTokenName', 'CI Pipeline')
        ->set('newTokenAbilities', ['read-products', 'write-products'])
        ->call('generateToken')
        ->assertHasNoErrors()
        ->assertSet('showTokenModal', false)
        ->assertSet('generatedToken', fn (?string $token): bool => $token !== null && str_starts_with($token, 'shop_'));

    $token = PersonalAccessToken::query()->sole();

    expect($token->name)->toBe('CI Pipeline')
        ->and($token->abilities)->toBe(['read-products', 'write-products']);
});

test('validates the token form', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('newTokenName', '')
        ->set('newTokenAbilities', [])
        ->call('generateToken')
        ->assertHasErrors(['newTokenName', 'newTokenAbilities']);

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

test('revokes a token via the UI', function () {
    $plain = app(\App\Services\ApiTokenService::class)->create($this->user, 'Temporary', ['read-products']);
    $token = PersonalAccessToken::query()->sole();

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->assertSee('Temporary')
        ->call('revokeToken', $token->id)
        ->assertDispatched('toast');

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

test('creates a webhook and shows the signing secret once', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('openWebhookModal')
        ->assertSet('showWebhookModal', true)
        ->assertSet('editingWebhookId', null)
        ->set('webhookEventType', 'order.paid')
        ->set('webhookUrl', 'https://example.com/hook')
        ->call('saveWebhook')
        ->assertHasNoErrors()
        ->assertSet('showWebhookModal', false)
        ->assertSet('generatedWebhookSecret', fn (?string $secret): bool => $secret !== null && str_starts_with($secret, 'whsec_'));

    $webhook = WebhookSubscription::query()->sole();

    expect($webhook->event_type)->toBe('order.paid')
        ->and($webhook->target_url)->toBe('https://example.com/hook')
        ->and($webhook->status)->toBe(WebhookSubscriptionStatus::Active)
        ->and($webhook->signing_secret_encrypted)->toStartWith('whsec_');
});

test('requires an https url for webhooks', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('webhookUrl', 'http://example.com/insecure')
        ->call('saveWebhook')
        ->assertHasErrors(['webhookUrl']);

    expect(WebhookSubscription::query()->count())->toBe(0);
});

test('edits a webhook', function () {
    $webhook = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/old',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('openWebhookModal', $webhook->id)
        ->assertSet('editingWebhookId', $webhook->id)
        ->assertSet('webhookEventType', 'order.created')
        ->assertSet('webhookUrl', 'https://example.com/old')
        ->set('webhookEventType', 'product.updated')
        ->set('webhookUrl', 'https://example.com/new')
        ->call('saveWebhook')
        ->assertHasNoErrors();

    $fresh = $webhook->fresh();

    expect($fresh->event_type)->toBe('product.updated')
        ->and($fresh->target_url)->toBe('https://example.com/new');
});

test('pauses and resumes a webhook', function () {
    $webhook = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('pauseWebhook', $webhook->id);

    expect($webhook->fresh()->status)->toBe(WebhookSubscriptionStatus::Paused);

    Livewire::test(Index::class)
        ->call('resumeWebhook', $webhook->id);

    expect($webhook->fresh()->status)->toBe(WebhookSubscriptionStatus::Active);
});

test('deletes a webhook with its deliveries', function () {
    $webhook = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);
    WebhookDelivery::factory()->count(2)->create(['subscription_id' => $webhook->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('deleteWebhook', $webhook->id)
        ->assertDispatched('toast');

    expect(WebhookSubscription::query()->count())->toBe(0)
        ->and(WebhookDelivery::query()->count())->toBe(0);
});

test('lists deliveries for a webhook', function () {
    $webhook = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);
    WebhookDelivery::factory()->failed()->create(['subscription_id' => $webhook->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('viewDeliveries', $webhook->id)
        ->assertSet('viewingDeliveriesFor', $webhook->id)
        ->assertSet('showDeliveriesModal', true)
        ->assertSee('Failed');
});

test('webhooks are scoped to the current store', function () {
    $other = $this->createStore();
    WebhookSubscription::factory()->create(['store_id' => $other->id, 'target_url' => 'https://other-store.example.com/hook']);
    WebhookSubscription::factory()->create(['store_id' => $this->store->id, 'target_url' => 'https://my-store.example.com/hook']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->assertSee('https://my-store.example.com/hook')
        ->assertDontSee('https://other-store.example.com/hook');
});

test('enforces the manage-developers gate', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/developers')
        ->assertForbidden();
});
