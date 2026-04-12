<?php

use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates an API token', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(DevelopersIndex::class)
        ->set('newTokenName', 'CI integration')
        ->call('createToken')
        ->assertSet('newTokenName', '');

    expect($user->fresh()->tokens()->count())->toBe(1)
        ->and($user->fresh()->tokens()->first()->name)->toBe('CI integration');
});

it('creates a webhook subscription', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(DevelopersIndex::class)
        ->set('webhookEventType', 'order.placed')
        ->set('webhookUrl', 'https://example.com/webhook')
        ->call('createWebhook');

    $webhook = WebhookSubscription::where('event_type', 'order.placed')->first();
    expect($webhook)->not->toBeNull()
        ->and($webhook->store_id)->toBe($store->id)
        ->and($webhook->url)->toBe('https://example.com/webhook');
});
