<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Events\OrderCreated;
use App\Jobs\DeliverWebhook;
use App\Models\Order;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

/**
 * Run a DeliverWebhook job by hand, swallowing the RuntimeException the job
 * throws to trigger a queue retry on non-final failed attempts.
 */
function runWebhookDeliveryJob(WebhookDelivery $delivery): DeliverWebhook
{
    $job = new DeliverWebhook($delivery, ['event' => 'order.created', 'data' => []], now()->getTimestamp());

    try {
        $job->handle(app(WebhookService::class));
    } catch (RuntimeException) {
        // In production the queue retries the job per its backoff schedule.
    }

    return $job;
}

it('delivers a webhook to a subscribed URL', function () {
    Http::fake();

    $subscription = WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/hooks/orders',
    ]);

    $order = Order::factory()->for($this->store)->create();

    event(new OrderCreated($order));

    Http::assertSent(function (Request $request) use ($order): bool {
        $payload = $request->data();

        return $request->url() === 'https://example.test/hooks/orders'
            && $request->header('X-Platform-Event') === ['order.created']
            && $request->header('X-Platform-Delivery-Id') !== []
            && $request->header('X-Platform-Timestamp') !== []
            && $payload['event'] === 'order.created'
            && $payload['data']['id'] === $order->getKey();
    });

    $this->assertDatabaseHas('webhook_deliveries', [
        'subscription_id' => $subscription->getKey(),
        'status' => 'success',
        'attempt_count' => 1,
        'response_code' => 200,
    ]);
});

it('signs the payload with HMAC', function () {
    Http::fake();

    WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'signing_secret_encrypted' => 'test-secret',
    ]);

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['id' => 42]);

    Http::assertSent(function (Request $request): bool {
        $expected = hash_hmac('sha256', $request->body(), 'test-secret');

        return $request->header('X-Platform-Signature') === [$expected];
    });
});

it('retries failed deliveries with exponential backoff', function () {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $subscription = WebhookSubscription::factory()->for($this->store)->create();

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
    ]);

    $job = runWebhookDeliveryJob($delivery);

    expect($job->tries)->toBe(6)
        ->and($job->backoff)->toBe([60, 300, 1800, 7200, 43200]);

    $delivery->refresh();

    expect($delivery->attempt_count)->toBe(1)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->response_code)->toBe(500);

    runWebhookDeliveryJob($delivery);

    expect($delivery->refresh()->attempt_count)->toBe(2)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Pending);
});

it('marks delivery as failed after max retries', function () {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $subscription = WebhookSubscription::factory()->for($this->store)->create();

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery, ['event' => 'order.created', 'data' => []], now()->getTimestamp());

    // The sixth and final attempt records the dead letter without throwing.
    $job->handle(app(WebhookService::class));

    $delivery->refresh();

    expect($delivery->attempt_count)->toBe(6)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->response_code)->toBe(500);
});

it('pauses subscription after circuit breaker threshold', function () {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $subscription = WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
    ]);

    foreach (range(1, 5) as $attempt) {
        $delivery = WebhookDelivery::factory()->create([
            'subscription_id' => $subscription->getKey(),
        ]);

        runWebhookDeliveryJob($delivery);
    }

    $subscription->refresh();

    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Paused)
        ->and($subscription->consecutive_failures)->toBe(5);

    // A paused subscription no longer receives new deliveries.
    $deliveriesBefore = WebhookDelivery::query()->count();

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['id' => 1]);

    expect(WebhookDelivery::query()->count())->toBe($deliveriesBefore);
});
