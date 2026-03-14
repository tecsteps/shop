<?php

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('delivers webhook payload to target URL', function () {
    Http::fake([
        'https://example.com/webhooks' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhooks',
        'secret' => 'test-secret-123',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 1],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new \App\Services\WebhookService);

    $delivery->refresh();
    expect($delivery->status)->toBe('success');
    expect($delivery->response_status)->toBe(200);
    expect($delivery->delivered_at)->not->toBeNull();
});

it('signs payload with HMAC-SHA256', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhooks',
        'secret' => 'my-secret',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 42],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new \App\Services\WebhookService);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event')
            && $request->header('X-Platform-Event')[0] === 'order.created'
            && $request->hasHeader('X-Platform-Delivery-Id')
            && $request->hasHeader('X-Platform-Timestamp');
    });
});

it('marks delivery as failed on non-2xx response', function () {
    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhooks',
        'secret' => 'test-secret',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 1],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new \App\Services\WebhookService);

    $delivery->refresh();
    expect($delivery->status)->toBe('failed');
    expect($delivery->response_status)->toBe(500);

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(1);
});

it('increments consecutive failures on repeated failures', function () {
    Http::fake([
        '*' => Http::response('Error', 503),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhooks',
        'secret' => 'test-secret',
        'status' => 'active',
        'consecutive_failures' => 3,
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 1],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new \App\Services\WebhookService);

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(4);
    expect($subscription->status)->toBe('active');
});

it('pauses subscription after 5 consecutive failures (circuit breaker)', function () {
    Http::fake([
        '*' => Http::response('Error', 500),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhooks',
        'secret' => 'test-secret',
        'status' => 'active',
        'consecutive_failures' => 4,
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 1],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new \App\Services\WebhookService);

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(5);
    expect($subscription->status)->toBe('paused');
});
