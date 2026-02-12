<?php

use App\Livewire\Admin\Developers\Index;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function developersAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the developers index page', function () {
    [$user, $store] = developersAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Webhooks')
        ->assertSuccessful();
});

test('it lists webhooks for the current store', function () {
    [$user, $store] = developersAdmin();

    WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'url' => 'https://example.com/hook',
        'topic' => 'orders/create',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('https://example.com/hook')
        ->assertSee('orders/create');
});

test('it does not show webhooks from other stores', function () {
    [$user, $store] = developersAdmin();

    $otherStore = Store::factory()->create();

    WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'url' => 'https://mystore.com/hook',
    ]);

    WebhookSubscription::factory()->create([
        'store_id' => $otherStore->id,
        'url' => 'https://othershop.com/hook',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('https://mystore.com/hook')
        ->assertDontSee('https://othershop.com/hook');
});

test('it creates a new webhook', function () {
    [$user, $store] = developersAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openWebhookModal')
        ->assertSet('showWebhookModal', true)
        ->set('webhookUrl', 'https://webhook.site/test')
        ->set('webhookTopic', 'orders/create')
        ->set('webhookSecret', 'my-secret')
        ->set('webhookActive', true)
        ->call('saveWebhook')
        ->assertSet('showWebhookModal', false)
        ->assertDispatched('toast');

    $webhook = WebhookSubscription::where('store_id', $store->id)
        ->where('url', 'https://webhook.site/test')
        ->first();

    expect($webhook)->not->toBeNull()
        ->and($webhook->topic)->toBe('orders/create')
        ->and($webhook->secret)->toBe('my-secret')
        ->and($webhook->is_active)->toBeTrue();
});

test('it edits an existing webhook', function () {
    [$user, $store] = developersAdmin();

    $webhook = WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'url' => 'https://old.example.com/hook',
        'topic' => 'orders/create',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openWebhookModal', $webhook->id)
        ->assertSet('webhookUrl', 'https://old.example.com/hook')
        ->set('webhookUrl', 'https://new.example.com/hook')
        ->set('webhookTopic', 'products/update')
        ->call('saveWebhook')
        ->assertDispatched('toast');

    $webhook->refresh();

    expect($webhook->url)->toBe('https://new.example.com/hook')
        ->and($webhook->topic)->toBe('products/update');
});

test('it deletes a webhook', function () {
    [$user, $store] = developersAdmin();

    $webhook = WebhookSubscription::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteWebhook', $webhook->id)
        ->assertDispatched('toast');

    expect(WebhookSubscription::find($webhook->id))->toBeNull();
});

test('it toggles webhook active state', function () {
    [$user, $store] = developersAdmin();

    $webhook = WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('toggleWebhook', $webhook->id)
        ->assertDispatched('toast');

    $webhook->refresh();
    expect($webhook->is_active)->toBeFalse();
});

test('it validates webhook URL is required', function () {
    [$user, $store] = developersAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openWebhookModal')
        ->set('webhookUrl', '')
        ->set('webhookTopic', 'orders/create')
        ->call('saveWebhook')
        ->assertHasErrors(['webhookUrl']);
});

test('it validates webhook topic is required', function () {
    [$user, $store] = developersAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openWebhookModal')
        ->set('webhookUrl', 'https://example.com')
        ->set('webhookTopic', '')
        ->call('saveWebhook')
        ->assertHasErrors(['webhookTopic']);
});

test('it generates a random secret for new webhooks', function () {
    [$user, $store] = developersAdmin();

    $component = Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openWebhookModal');

    $secret = $component->get('webhookSecret');
    expect($secret)->not->toBeEmpty()
        ->and(strlen($secret))->toBe(32);
});

test('it shows empty state when no webhooks exist', function () {
    [$user, $store] = developersAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No webhooks');
});
