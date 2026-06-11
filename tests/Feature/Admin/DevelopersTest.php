<?php

use App\Enums\StoreUserRole;
use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the developers page for an owner', function () {
    actingAsAdmin($this->user)
        ->get('/admin/developers')
        ->assertOk()
        ->assertSee('API tokens');
});

it('generates a token and shows the plain text value once', function () {
    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->set('newTokenName', 'My integration')
        ->set('newTokenAbilities', ['read-products', 'write-products'])
        ->call('generateToken')
        ->assertHasNoErrors()
        ->assertSet('generatedToken', fn (?string $token): bool => $token !== null && str_contains($token, 'shop_'));

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $this->user->getKey(),
        'name' => 'My integration',
    ]);
});

it('requires at least one ability when generating a token', function () {
    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->set('newTokenName', 'No abilities')
        ->set('newTokenAbilities', [])
        ->call('generateToken')
        ->assertHasErrors('newTokenAbilities');
});

it('revokes a token', function () {
    $token = $this->user->createToken('Revocable', ['read-products']);

    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('revokeToken', $token->accessToken->getKey());

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('creates a webhook subscription and shows the signing secret once', function () {
    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('openWebhookModal')
        ->set('webhookEventType', 'order.created')
        ->set('webhookUrl', 'https://example.test/hooks/orders')
        ->call('saveWebhook')
        ->assertHasNoErrors()
        ->assertSet('generatedWebhookSecret', fn (?string $secret): bool => $secret !== null && str_starts_with($secret, 'whsec_'));

    $this->assertDatabaseHas('webhook_subscriptions', [
        'store_id' => $this->store->getKey(),
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/hooks/orders',
        'status' => 'active',
    ]);
});

it('validates the webhook event type and endpoint URL', function () {
    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('openWebhookModal')
        ->set('webhookEventType', 'invalid.event')
        ->set('webhookUrl', 'not-a-url')
        ->call('saveWebhook')
        ->assertHasErrors(['webhookEventType', 'webhookUrl']);

    expect(WebhookSubscription::query()->count())->toBe(0);
});

it('updates an existing webhook subscription', function () {
    $webhook = WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/old',
    ]);

    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('openWebhookModal', $webhook->getKey())
        ->assertSet('webhookEventType', 'order.created')
        ->assertSet('webhookUrl', 'https://example.test/old')
        ->set('webhookEventType', 'order.paid')
        ->set('webhookUrl', 'https://example.test/new')
        ->call('saveWebhook')
        ->assertHasNoErrors()
        ->assertSet('generatedWebhookSecret', null);

    $webhook->refresh();

    expect($webhook->event_type)->toBe('order.paid')
        ->and($webhook->target_url)->toBe('https://example.test/new');
});

it('pauses and resumes a webhook subscription', function () {
    $webhook = WebhookSubscription::factory()->for($this->store)->create([
        'consecutive_failures' => 3,
    ]);

    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('toggleWebhookStatus', $webhook->getKey());

    expect($webhook->refresh()->status)->toBe(WebhookSubscriptionStatus::Paused);

    Livewire::test(DevelopersIndex::class)
        ->call('toggleWebhookStatus', $webhook->getKey());

    $webhook->refresh();

    expect($webhook->status)->toBe(WebhookSubscriptionStatus::Active)
        ->and($webhook->consecutive_failures)->toBe(0);
});

it('deletes a webhook subscription', function () {
    $webhook = WebhookSubscription::factory()->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('deleteWebhook', $webhook->getKey());

    $this->assertDatabaseMissing('webhook_subscriptions', [
        'id' => $webhook->getKey(),
    ]);
});

it('lists webhook subscriptions with recent deliveries and response codes', function () {
    $webhook = WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/hooks/orders',
    ]);

    WebhookDelivery::factory()->succeeded()->create([
        'subscription_id' => $webhook->getKey(),
    ]);

    actingAsAdmin($this->user)
        ->get('/admin/developers')
        ->assertOk()
        ->assertSee('order.created')
        ->assertSee('https://example.test/hooks/orders')
        ->assertSee('Recent deliveries')
        ->assertSee('200');
});

it('restricts the developers page to owner and admin roles', function () {
    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/developers')
        ->assertForbidden();

    $admin = createStoreMember($this->store, StoreUserRole::Admin);

    actingAsAdmin($admin, $this->store)
        ->get('/admin/developers')
        ->assertOk();
});
