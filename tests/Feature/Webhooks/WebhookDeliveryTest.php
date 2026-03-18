<?php

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('dispatches webhook delivery jobs for matching subscriptions', function () {
    Queue::fake();

    $sub = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'active',
    ]);

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class);

    $delivery = WebhookDelivery::where('subscription_id', $sub->id)->first();
    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('pending');
});

it('does not dispatch for paused subscriptions', function () {
    Queue::fake();

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'paused',
    ]);

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});

it('does not dispatch for non-matching event types', function () {
    Queue::fake();

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.paid',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'active',
    ]);

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});

it('delivers a webhook successfully and records response', function () {
    Http::fake([
        'example.com/hook' => Http::response('OK', 200),
    ]);

    $sub = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $sub->id,
        'event_id' => 'evt-123',
        'attempt_count' => 0,
        'status' => 'pending',
    ]);

    (new DeliverWebhook($delivery))->handle(app(WebhookService::class));

    $delivery->refresh();
    expect($delivery->status)->toBe('success')
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->response_body_snippet)->toBe('OK');
});

it('sends correct headers with webhook delivery', function () {
    Http::fake(function ($request) {
        expect($request->hasHeader('X-Platform-Signature'))->toBeTrue()
            ->and($request->hasHeader('X-Platform-Event'))->toBeTrue()
            ->and($request->hasHeader('X-Platform-Delivery-Id'))->toBeTrue()
            ->and($request->hasHeader('X-Platform-Timestamp'))->toBeTrue()
            ->and($request->header('X-Platform-Event')[0])->toBe('order.created')
            ->and($request->header('X-Platform-Delivery-Id')[0])->toBe('evt-456');

        return Http::response('OK', 200);
    });

    $sub = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $sub->id,
        'event_id' => 'evt-456',
        'attempt_count' => 0,
        'status' => 'pending',
    ]);

    (new DeliverWebhook($delivery))->handle(app(WebhookService::class));
});

it('marks delivery as failed after max attempts', function () {
    Http::fake([
        'example.com/hook' => Http::response('Error', 500),
    ]);

    $sub = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $sub->id,
        'event_id' => 'evt-789',
        'attempt_count' => 5,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);

    try {
        $job->handle(app(WebhookService::class));
    } catch (\Throwable) {
        // release may throw in test context
    }

    $delivery->refresh();
    expect($delivery->status)->toBe('failed')
        ->and($delivery->attempt_count)->toBe(6)
        ->and($delivery->response_code)->toBe(500);
});

it('pauses subscription after 5 consecutive failed deliveries (circuit breaker)', function () {
    Http::fake([
        'example.com/hook' => Http::response('Error', 500),
    ]);

    $sub = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'active',
    ]);

    // Create 4 prior failed deliveries
    for ($i = 0; $i < 4; $i++) {
        WebhookDelivery::create([
            'subscription_id' => $sub->id,
            'event_id' => "evt-fail-{$i}",
            'attempt_count' => 6,
            'status' => 'failed',
            'last_attempt_at' => now(),
        ]);
    }

    // 5th delivery that will fail
    $delivery = WebhookDelivery::create([
        'subscription_id' => $sub->id,
        'event_id' => 'evt-fail-final',
        'attempt_count' => 5,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery);

    try {
        $job->handle(app(WebhookService::class));
    } catch (\Throwable) {
        // release may throw in test context
    }

    $sub->refresh();
    expect($sub->status)->toBe('paused');
});

it('skips delivery if subscription is no longer active', function () {
    Http::fake();

    $sub = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'secret123',
        'status' => 'paused',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $sub->id,
        'event_id' => 'evt-skip',
        'attempt_count' => 0,
        'status' => 'pending',
    ]);

    (new DeliverWebhook($delivery))->handle(app(WebhookService::class));

    Http::assertNothingSent();
    $delivery->refresh();
    expect($delivery->status)->toBe('pending')
        ->and($delivery->attempt_count)->toBe(0);
});

it('has correct retry backoff configuration', function () {
    $delivery = WebhookDelivery::factory()->make();
    $job = new DeliverWebhook($delivery);

    expect($job->tries)->toBe(6)
        ->and($job->backoff)->toBe([60, 300, 1800, 7200, 43200]);
});
