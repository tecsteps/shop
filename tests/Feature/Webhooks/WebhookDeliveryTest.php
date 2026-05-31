<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * Attach a fake async (non-sync) queue Job to the delivery job so its failure
 * path takes the retry branch (re-throw) instead of terminating inline. Without
 * this, a directly-invoked job is treated as synchronous and swallows failures.
 */
function asAsyncJob(DeliverWebhook $job, int $attempts = 1): DeliverWebhook
{
    $queueJob = Mockery::mock(JobContract::class);
    $queueJob->shouldReceive('attempts')->andReturn($attempts);
    $queueJob->shouldReceive('getConnectionName')->andReturn('database');
    $job->setJob($queueJob);

    return $job;
}

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('delivers a webhook to a subscribed URL', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/hook',
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    app()->call([new DeliverWebhook($delivery->id, 'order.created', ['id' => 1]), 'handle']);

    Http::assertSent(fn ($request) => $request->url() === 'https://example.test/hook'
        && $request->method() === 'POST');

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Success);
});

it('signs the payload with HMAC', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    $secret = 'whsec_known_secret';
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'signing_secret_encrypted' => $secret,
        'target_url' => 'https://example.test/hook',
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    app()->call([new DeliverWebhook($delivery->id, 'order.created', ['id' => 7]), 'handle']);

    Http::assertSent(function ($request) use ($secret) {
        $signature = $request->header('X-Platform-Signature')[0] ?? null;
        $valid = app(WebhookService::class)->verify($request->body(), (string) $signature, $secret);

        return $signature !== null && $valid;
    });
});

it('retries failed deliveries with exponential backoff on an async queue', function () {
    Http::fake(['*' => Http::response('boom', 500)]);

    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    $job = asAsyncJob(new DeliverWebhook($delivery->id, 'order.created', ['id' => 1]), attempts: 1);

    expect($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);

    // On a real (async) queue with attempts remaining, a failure re-throws so
    // the queue applies backoff and retries.
    expect(fn () => $job->handle(app(WebhookService::class)))->toThrow(RuntimeException::class);

    expect($delivery->fresh()->attempt_count)->toBe(1)
        ->and($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Pending);
});

it('does NOT throw and records a failed delivery when running synchronously', function () {
    // The exact production hazard: sync queue + unreachable endpoint must never
    // propagate into the dispatching request. A directly-invoked job (no queue
    // job attached) is treated as synchronous.
    Http::fake(fn () => throw new ConnectionException('cURL error 7: connection refused'));

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://unreachable.test/hook',
    ]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    $job = new DeliverWebhook($delivery->id, 'order.created', ['id' => 1]);

    // Must not throw.
    $job->handle(app(WebhookService::class));

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->fresh()->attempt_count)->toBe(1);
});

it('marks delivery as failed after max retries', function () {
    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 6,
    ]);

    $job = new DeliverWebhook($delivery->id, 'order.created', ['id' => 1]);

    expect($job->tries)->toBe(6);

    $job->failed(new RuntimeException('exhausted'));

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Failed);
});

it('pauses subscription after circuit breaker threshold', function () {
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'status' => WebhookSubscriptionStatus::Active->value,
    ]);

    // Four prior failed deliveries plus the fifth one we mark failed here.
    WebhookDelivery::factory()->count(4)->failed()->create([
        'subscription_id' => $subscription->id,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 6,
    ]);

    (new DeliverWebhook($delivery->id, 'order.created', ['id' => 1]))->failed(new RuntimeException('fail'));

    expect($subscription->fresh()->status)->toBe(WebhookSubscriptionStatus::Paused);
});

it('queues a delivery job per matching active subscription', function () {
    Queue::fake();

    WebhookSubscription::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
    ]);

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.updated',
    ]);

    $queued = app(WebhookService::class)->dispatch($this->store, 'order.created', ['id' => 1]);

    expect($queued)->toBe(2);
    Queue::assertPushed(DeliverWebhook::class, 2);
});

it('completes checkout even when the webhook endpoint is unreachable (sync queue regression)', function () {
    // Exact production repro: sync queue + active subscription to an unreachable
    // URL. OrderCreated -> DispatchOrderCreatedWebhook -> DeliverWebhook runs
    // INLINE; the connection failure must NOT propagate into order creation.
    Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect'));

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://apps.example.test/webhooks/order-created',
        'status' => WebhookSubscriptionStatus::Active->value,
    ]);

    $checkout = startCheckout(['price' => 5000]);
    $service = app(\App\Services\CheckoutService::class);
    $service->setAddress($checkout, germanAddressData());

    $zone = \App\Models\ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = \App\Models\ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $service->setShippingMethod($checkout->fresh(), $rate->id);
    $service->selectPaymentMethod($checkout->fresh(), App\Enums\PaymentMethod::CreditCard);

    // Must NOT throw despite the unreachable webhook endpoint.
    $order = $service->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);

    expect($order->status->value)->toBe('paid');
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);

    // The delivery was attempted and recorded as failed (not lost, not retried inline).
    $this->assertDatabaseHas('webhook_deliveries', ['status' => WebhookDeliveryStatus::Failed->value]);
});
