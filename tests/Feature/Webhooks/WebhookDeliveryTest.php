<?php

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

test('records successful delivery', function () {
    Http::fake([
        'example.com/*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.created'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => true,
        'consecutive_failures' => 2,
    ]);

    $job = new DeliverWebhook($subscription, 'order.created', ['order_id' => 1]);
    $job->handle();

    $delivery = WebhookDelivery::where('subscription_id', $subscription->id)->first();

    expect($delivery)->not->toBeNull();
    expect($delivery->event_type)->toBe('order.created');
    expect($delivery->response_status)->toBe(200);
    expect($delivery->delivered_at)->not->toBeNull();

    // Consecutive failures should be reset
    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(0);
});

test('records failed delivery with response', function () {
    Http::fake([
        'example.com/*' => Http::response('Internal Server Error', 500),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.created'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => true,
        'consecutive_failures' => 0,
    ]);

    $job = new DeliverWebhook($subscription, 'order.created', ['order_id' => 1]);
    $job->handle();

    $delivery = WebhookDelivery::where('subscription_id', $subscription->id)->first();

    expect($delivery)->not->toBeNull();
    expect($delivery->response_status)->toBe(500);
    expect($delivery->response_body)->toBe('Internal Server Error');
    expect($delivery->delivered_at)->toBeNull();

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(1);
});

test('increments consecutive failures on error', function () {
    Http::fake([
        'example.com/*' => Http::response('Bad Gateway', 502),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.created'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => true,
        'consecutive_failures' => 3,
    ]);

    $job = new DeliverWebhook($subscription, 'order.created', ['order_id' => 1]);
    $job->handle();

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(4);
    expect($subscription->is_active)->toBeTrue();
});

test('pauses subscription after 5 consecutive failures', function () {
    Http::fake([
        'example.com/*' => Http::response('Service Unavailable', 503),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.created'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => true,
        'consecutive_failures' => 4,
    ]);

    $job = new DeliverWebhook($subscription, 'order.created', ['order_id' => 1]);
    $job->handle();

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(5);
    expect($subscription->is_active)->toBeFalse();
    expect($subscription->paused_at)->not->toBeNull();
});
