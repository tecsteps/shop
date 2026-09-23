<?php

use App\Livewire\Admin\Developers\Index;
use App\Models\PersonalAccessToken;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Services\ApiTokenService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

function developersAdmin(Store $store, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role]);

    return $user;
}

it('renders the developers page in the admin navigation context', function () {
    $store = shopStore();
    $user = developersAdmin($store);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get(route('admin.developers'))
        ->assertOk()
        ->assertSee('API tokens')
        ->assertSee('Webhooks')
        ->assertSee('Developers');
});

it('creates a one-time hashed token scoped to the current store', function () {
    $store = shopStore();
    $otherStore = shopStore('developers-other.test');
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $otherStoreToken = app(ApiTokenService::class)->create($user, $otherStore, 'Other store key', ['read-orders']);

    $component = Livewire::actingAs($user)->test(Index::class)
        ->assertDontSee('Other store key')
        ->set('newTokenName', 'Store integration')
        ->set('tokenAbilities', ['read-products', 'read-orders'])
        ->call('generateToken')
        ->assertHasNoErrors();

    $plainTextToken = $component->get('generatedToken');
    $token = PersonalAccessToken::query()->where('store_id', $store->id)->where('name', 'Store integration')->firstOrFail();

    expect($plainTextToken)->toStartWith('shop_')
        ->and($token->token)->toBe(hash('sha256', $plainTextToken))
        ->and($token->abilities)->toBe(['read-products', 'read-orders'])
        ->and($token->store_id)->toBe($store->id)
        ->and($otherStoreToken['token']->store_id)->toBe($otherStore->id);

    $component->assertSee($plainTextToken)
        ->call('dismissGeneratedToken')
        ->assertDontSee($plainTextToken);
});

it('rejects empty or unsupported API permissions before issuing a token', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = developersAdmin($store);

    Livewire::actingAs($user)->test(Index::class)
        ->set('newTokenName', 'Invalid permissions')
        ->set('tokenAbilities', ['manage-platform'])
        ->call('generateToken')
        ->assertHasErrors('tokenAbilities.0');

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('revokes only a token scoped to the current store', function () {
    $store = shopStore();
    $otherStore = shopStore('developers-revoke-other.test');
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $tokenService = app(ApiTokenService::class);
    $localToken = $tokenService->create($user, $store, 'Local API key', ['read-products'])['token'];
    $foreignToken = $tokenService->create($user, $otherStore, 'Foreign API key', ['read-products'])['token'];

    expect(fn () => Livewire::actingAs($user)->test(Index::class)->call('revokeToken', $foreignToken->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertModelExists($foreignToken);

    Livewire::actingAs($user)->test(Index::class)
        ->call('revokeToken', $localToken->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $localToken->id]);
    $this->assertModelExists($foreignToken);
});

it('creates updates and deletes encrypted store-scoped webhook subscriptions', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = developersAdmin($store);

    $component = Livewire::actingAs($user)->test(Index::class)
        ->call('openWebhookModal')
        ->set('webhookEventType', 'order.created')
        ->set('webhookUrl', 'https://hooks.example.test/orders')
        ->set('webhookStatus', 'active')
        ->call('saveWebhook')
        ->assertHasNoErrors()
        ->assertSee('order.created')
        ->assertSee('https://hooks.example.test/orders');

    $webhook = WebhookSubscription::withoutGlobalScopes()->where('store_id', $store->id)->firstOrFail();
    $rawSecret = DB::table('webhook_subscriptions')->where('id', $webhook->id)->value('signing_secret_encrypted');
    $originalSecret = $webhook->signing_secret_encrypted;

    expect($webhook->event_type)->toBe('order.created')
        ->and($webhook->status)->toBe('active')
        ->and($originalSecret)->toHaveLength(64)
        ->and($rawSecret)->not->toBe($originalSecret);

    $component->call('openWebhookModal', $webhook->id)
        ->set('webhookEventType', 'order.paid')
        ->set('webhookUrl', 'https://hooks.example.test/updated-orders')
        ->set('webhookStatus', 'paused')
        ->call('saveWebhook')
        ->assertHasNoErrors();

    $webhook->refresh();
    expect($webhook->event_type)->toBe('order.paid')
        ->and($webhook->target_url)->toBe('https://hooks.example.test/updated-orders')
        ->and($webhook->status)->toBe('paused')
        ->and($webhook->signing_secret_encrypted)->toBe($originalSecret);

    $component->call('deleteWebhook', $webhook->id)->assertHasNoErrors();
    $this->assertDatabaseMissing('webhook_subscriptions', ['id' => $webhook->id]);
});

it('rejects unsupported webhook events and non-http endpoint URLs', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = developersAdmin($store);

    Livewire::actingAs($user)->test(Index::class)
        ->call('openWebhookModal')
        ->set('webhookEventType', 'customer.deleted')
        ->set('webhookUrl', 'ftp://hooks.example.test/events')
        ->set('webhookStatus', 'active')
        ->call('saveWebhook')
        ->assertHasErrors(['webhookEventType', 'webhookUrl']);

    expect(WebhookSubscription::withoutGlobalScopes()->count())->toBe(0);
});

it('does not reveal or delete another stores webhook by identifier', function () {
    $store = shopStore();
    $otherStore = shopStore('developers-webhook-other.test');
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $foreignWebhook = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'event_type' => 'order.created',
        'target_url' => 'https://foreign.example.test/hook',
        'signing_secret_encrypted' => bin2hex(random_bytes(32)),
        'status' => 'active',
    ]);

    Livewire::actingAs($user)->test(Index::class)
        ->assertDontSee('foreign.example.test');

    expect(fn () => Livewire::actingAs($user)->test(Index::class)->call('openWebhookModal', $foreignWebhook->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertModelExists($foreignWebhook);
});

it('authorizes developers settings for owners and admins only', function () {
    $store = shopStore();
    app()->instance('current_store', $store);

    foreach (['owner', 'admin'] as $role) {
        $user = developersAdmin($store, $role);
        expect(Gate::forUser($user)->allows('manage-developers'))->toBeTrue();
    }

    foreach (['staff', 'support'] as $role) {
        $user = developersAdmin($store, $role);
        expect(Gate::forUser($user)->allows('manage-developers'))->toBeFalse();
    }
});

it('forbids staff from opening developer settings', function () {
    $store = shopStore();
    $staff = developersAdmin($store, 'staff');
    app()->instance('current_store', $store);

    Livewire::actingAs($staff)->test(Index::class)->assertForbidden();
});
