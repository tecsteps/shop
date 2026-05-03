<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Models\ApiToken;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($this->user);
    session(['current_store_id' => $this->store->id]);
    app()->instance('current_store', $this->store);
});

test('admin can generate and revoke api tokens from the developer page', function (): void {
    Livewire::test(DevelopersIndex::class)
        ->assertSet('availableAbilities', ApiToken::availableAbilities(includePlatform: true))
        ->assertSee('write-products')
        ->assertSee('write-settings')
        ->assertSee('write-content')
        ->assertSee('write-themes')
        ->assertSee('manage-platform')
        ->set('newTokenName', 'Operations sync')
        ->set('tokenAbilities', [
            'read-analytics',
            'write-products',
            'write-settings',
            'write-content',
            'write-themes',
            'manage-platform',
        ])
        ->call('generateToken')
        ->assertHasNoErrors()
        ->assertSet('newTokenName', '')
        ->assertSet('tokenAbilities', ApiToken::DefaultAbilities)
        ->assertSee('shop_');

    $token = ApiToken::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('name', 'Operations sync')
        ->firstOrFail();

    expect($token->abilities_json)->toBe([
        'read-analytics',
        'write-products',
        'write-settings',
        'write-content',
        'write-themes',
        'manage-platform',
    ])
        ->and($token->revoked_at)->toBeNull();

    Livewire::test(DevelopersIndex::class)
        ->call('revokeToken', $token->id)
        ->assertHasNoErrors();

    expect($token->fresh()->revoked_at)->not->toBeNull();
});

test('developer token ability validation follows the exposed ability list', function (): void {
    Livewire::test(DevelopersIndex::class)
        ->set('newTokenName', 'Invalid scope')
        ->set('tokenAbilities', ['delete-everything'])
        ->call('generateToken')
        ->assertHasErrors(['tokenAbilities.0']);

    $staff = User::factory()->create();
    StoreUser::query()->create([
        'store_id' => $this->store->id,
        'user_id' => $staff->id,
        'role' => StoreUserRole::Staff->value,
    ]);

    $this->actingAs($staff);

    Livewire::test(DevelopersIndex::class)
        ->assertSet('availableAbilities', ApiToken::StoreAbilities)
        ->assertDontSee('manage-platform')
        ->set('newTokenName', 'Platform scope')
        ->set('tokenAbilities', ['manage-platform'])
        ->call('generateToken')
        ->assertHasErrors(['tokenAbilities.0']);

    expect(ApiToken::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereIn('name', ['Invalid scope', 'Platform scope'])
        ->exists())->toBeFalse();
});

test('admin can create edit and delete webhook subscriptions', function (): void {
    Livewire::test(DevelopersIndex::class)
        ->set('webhookEventType', 'order.paid')
        ->set('webhookUrl', 'https://example.com/hooks/orders')
        ->call('saveWebhook')
        ->assertHasNoErrors()
        ->assertSet('editingWebhookId', null)
        ->assertSet('webhookUrl', '')
        ->assertSee('order.paid');

    $webhook = WebhookSubscription::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('target_url', 'https://example.com/hooks/orders')
        ->firstOrFail();

    $signingSecret = $webhook->signing_secret_encrypted;

    Livewire::test(DevelopersIndex::class)
        ->call('editWebhook', $webhook->id)
        ->assertSet('editingWebhookId', $webhook->id)
        ->assertSet('webhookEventType', 'order.paid')
        ->set('webhookUrl', 'https://example.com/hooks/orders-updated')
        ->call('saveWebhook')
        ->assertHasNoErrors();

    expect($webhook->fresh()->target_url)->toBe('https://example.com/hooks/orders-updated')
        ->and($webhook->fresh()->signing_secret_encrypted)->toBe($signingSecret);

    Livewire::test(DevelopersIndex::class)
        ->call('deleteWebhook', $webhook->id)
        ->assertHasNoErrors();

    expect(WebhookSubscription::withoutGlobalScopes()->whereKey($webhook->id)->exists())->toBeFalse();
});
