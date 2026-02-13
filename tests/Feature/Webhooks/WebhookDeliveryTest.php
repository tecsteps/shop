<?php

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('delivers a webhook to a subscribed URL', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response(['ok' => true], 200),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'secret' => 'test-secret-key',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 123],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new WebhookService);

    $delivery->refresh();

    expect($delivery->status)->toBe('success')
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->completed_at)->not->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook'
            && $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event')
            && $request->header('X-Platform-Event')[0] === 'order.created';
    });
});

it('signs the payload with HMAC', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response(['ok' => true], 200),
    ]);

    $secret = 'my-webhook-secret';

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'secret' => $secret,
        'status' => 'active',
    ]);

    $payload = ['order_id' => 456];

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => $payload,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new WebhookService);

    $expectedSignature = hash_hmac('sha256', json_encode($payload), $secret);

    Http::assertSent(function ($request) use ($expectedSignature) {
        return $request->header('X-Platform-Signature')[0] === $expectedSignature;
    });
});

it('retries failed deliveries with exponential backoff', function () {
    $job = new DeliverWebhook(WebhookDelivery::factory()->make());

    expect($job->tries)->toBe(6)
        ->and($job->maxExceptions)->toBe(6)
        ->and($job->backoff)->toBe([60, 300, 1800, 7200, 43200]);
});

it('marks delivery as failed after max retries', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response('Server Error', 500),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'secret' => 'test-secret',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 789],
        'status' => 'pending',
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new WebhookService);

    $delivery->refresh();

    expect($delivery->status)->toBe('failed')
        ->and($delivery->attempt_count)->toBe(6)
        ->and($delivery->completed_at)->not->toBeNull();
});

it('pauses subscription after circuit breaker threshold', function () {
    Http::fake([
        'https://example.com/webhook' => Http::response('Server Error', 500),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'secret' => 'test-secret',
        'status' => 'active',
        'consecutive_failures' => 4,
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_type' => 'order.created',
        'payload_json' => ['order_id' => 999],
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);
    $job->handle(new WebhookService);

    $subscription->refresh();

    expect($subscription->status)->toBe('paused')
        ->and($subscription->consecutive_failures)->toBe(5);
});
