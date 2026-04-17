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
    $this->store = $this->context['store'];
    $this->service = new WebhookService;
});

it('delivers a webhook to a subscribed URL', function () {
    Http::fake([
        'https://example.com/hook' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => 'active',
    ]);

    $payload = ['order_id' => 1];
    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_id' => 'test-event-id',
        'attempt_count' => 0,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery, 'order.created', $payload);
    $job->handle($this->service);

    $delivery->refresh();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/hook'
            && $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event')
            && $request->hasHeader('X-Platform-Delivery-Id')
            && $request->hasHeader('X-Platform-Timestamp');
    });
});

it('signs the payload with HMAC', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $secret = 'my-signing-secret';
    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => $secret,
        'status' => 'active',
    ]);

    $payload = ['order_id' => 42];
    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_id' => 'test-event-id',
        'attempt_count' => 0,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery, 'order.created', $payload);
    $job->handle($this->service);

    Http::assertSent(function ($request) use ($payload, $secret) {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $secret);

        return $request->header('X-Platform-Signature')[0] === $expectedSignature
            && $request->header('X-Platform-Event')[0] === 'order.created';
    });
});

it('retries failed deliveries with exponential backoff', function () {
    $job = new DeliverWebhook(
        WebhookDelivery::factory()->create(),
        'order.created',
        ['order_id' => 1]
    );

    expect($job->tries)->toBe(6)
        ->and($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);
});

it('marks delivery as failed after max retries', function () {
    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => 'active',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_id' => 'test-event-id',
        'attempt_count' => 5,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery, 'order.created', ['order_id' => 1]);
    $job->failed(new \RuntimeException('Connection failed'));

    $delivery->refresh();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed);
});

it('pauses subscription after circuit breaker threshold', function () {
    $subscription = WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => 'active',
    ]);

    // Create 4 prior failed deliveries
    for ($i = 0; $i < 4; $i++) {
        WebhookDelivery::create([
            'subscription_id' => $subscription->id,
            'event_id' => "failed-event-{$i}",
            'attempt_count' => 6,
            'status' => 'failed',
            'last_attempt_at' => now()->toIso8601String(),
        ]);
    }

    // Create the 5th delivery that will fail
    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_id' => 'failing-event-5',
        'attempt_count' => 5,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery, 'order.created', ['order_id' => 1]);
    $job->failed(new \RuntimeException('Connection failed'));

    $subscription->refresh();
    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Paused);
});

it('dispatches jobs only for active subscriptions', function () {
    Queue::fake();

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://active.com/hook',
        'signing_secret_encrypted' => 'secret',
        'status' => 'active',
    ]);

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://paused.com/hook',
        'signing_secret_encrypted' => 'secret',
        'status' => 'paused',
    ]);

    $this->service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class, 1);
});
