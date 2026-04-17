<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->context = createStoreContext();
});

it('delivers a webhook to a subscribed URL', function () {
    Http::fake([
        'https://example.com/webhooks' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhooks',
        'signing_secret_encrypted' => 'test-secret',
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    $job = new DeliverWebhook($delivery->id, ['event' => 'order.created']);
    $job->handle(new WebhookService);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhooks'
            && $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event')
            && $request->hasHeader('X-Platform-Delivery-Id')
            && $request->hasHeader('X-Platform-Timestamp');
    });

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Success);
});

it('signs the payload with HMAC', function () {
    Http::fake([
        'https://example.com/webhooks' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
        'signing_secret_encrypted' => 'my-secret',
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    $payload = ['event' => 'order.created'];
    $job = new DeliverWebhook($delivery->id, $payload);
    $job->handle(new WebhookService);

    $expectedSignature = hash_hmac('sha256', json_encode($payload), 'my-secret');

    Http::assertSent(function ($request) use ($expectedSignature) {
        return $request->header('X-Platform-Signature')[0] === $expectedSignature;
    });
});

it('retries failed deliveries with exponential backoff', function () {
    Http::fake([
        'https://example.com/webhooks' => Http::response('Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    $job = new DeliverWebhook($delivery->id, ['event' => 'order.created']);
    $job->handle(new WebhookService);

    $delivery->refresh();
    expect($delivery->attempt_count)->toBe(1)
        ->and($delivery->response_code)->toBe(500)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Pending);
});

it('marks delivery as failed after max retries', function () {
    Http::fake([
        'https://example.com/webhooks' => Http::response('Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery->id, ['event' => 'order.created']);
    $job->handle(new WebhookService);

    $delivery->refresh();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempt_count)->toBe(6);
});

it('pauses subscription after circuit breaker threshold', function () {
    Http::fake([
        'https://example.com/webhooks' => Http::response('Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    // Create 4 previously failed deliveries
    for ($i = 0; $i < 4; $i++) {
        WebhookDelivery::factory()->failed()->create([
            'subscription_id' => $subscription->id,
        ]);
    }

    // The 5th delivery attempt that will also fail
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery->id, ['event' => 'order.created']);
    $job->handle(new WebhookService);

    $subscription->refresh();
    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Paused);
});

it('dispatches webhooks for matching subscriptions', function () {
    Queue::fake();

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
        'event_type' => 'order.created',
    ]);

    // Non-matching subscription
    WebhookSubscription::factory()->create([
        'store_id' => $this->context['store']->id,
        'event_type' => 'product.updated',
    ]);

    $service = new WebhookService;
    $service->dispatch($this->context['store'], 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class, 1);

    expect(WebhookDelivery::where('subscription_id', $subscription->id)->count())->toBe(1);
});

it('skips paused subscriptions when dispatching', function () {
    Queue::fake();

    WebhookSubscription::factory()->paused()->create([
        'store_id' => $this->context['store']->id,
        'event_type' => 'order.created',
    ]);

    $service = new WebhookService;
    $service->dispatch($this->context['store'], 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});
