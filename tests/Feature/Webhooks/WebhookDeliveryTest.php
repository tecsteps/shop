<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->store = $this->createStore();
});

function runDelivery(WebhookSubscription $subscription, string $eventType = 'order.created', array $payload = ['id' => 50]): DeliverWebhook
{
    $job = new DeliverWebhook($subscription, $eventType, $payload);

    try {
        $job->handle(app(WebhookService::class));
    } catch (RuntimeException) {
        // Expected while retries remain; the queue would re-dispatch.
    }

    return $job;
}

test('queues a delivery job for matching active subscriptions only', function () {
    Queue::fake();

    $matching = WebhookSubscription::factory()->create(['store_id' => $this->store->id, 'event_type' => 'order.created']);
    WebhookSubscription::factory()->paused()->create(['store_id' => $this->store->id, 'event_type' => 'order.created']);
    WebhookSubscription::factory()->create(['store_id' => $this->store->id, 'event_type' => 'product.created']);
    WebhookSubscription::factory()->create(['event_type' => 'order.created']); // different store

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['id' => 1]);

    Queue::assertPushed(DeliverWebhook::class, 1);
    Queue::assertPushed(DeliverWebhook::class, fn (DeliverWebhook $job): bool => $job->subscription->id === $matching->id
        && $job->eventType === 'order.created'
        && $job->payload === ['id' => 1]);
});

test('delivers a webhook to the subscribed URL with signed headers', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hook',
        'signing_secret_encrypted' => 'test-secret',
    ]);

    $job = new DeliverWebhook($subscription, 'order.created', ['id' => 50]);
    $job->handle(app(WebhookService::class));

    Http::assertSent(function (Request $request) use ($job): bool {
        $timestamp = (int) $request->header('X-Platform-Timestamp')[0];
        $signature = $request->header('X-Platform-Signature')[0];

        return $request->url() === 'https://example.com/hook'
            && $request->method() === 'POST'
            && str_contains($request->header('Content-Type')[0], 'application/json')
            && $request->header('X-Platform-Event')[0] === 'order.created'
            && $request->header('X-Platform-Delivery-Id')[0] === $job->deliveryId
            && $request->body() === json_encode(['id' => 50])
            && app(WebhookService::class)->verify($request->body(), $signature, 'test-secret', $timestamp);
    });
});

test('records a successful delivery', function () {
    Http::fake(['*' => Http::response('{"ok":true}', 201)]);

    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);

    runDelivery($subscription);

    $delivery = WebhookDelivery::query()->sole();

    expect($delivery->subscription_id)->toBe($subscription->id)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(201)
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->last_attempt_at)->not->toBeNull()
        ->and($subscription->fresh()->consecutiveFailures())->toBe(0);
});

test('records failed deliveries and throws while retries remain', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);

    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);

    $job = new DeliverWebhook($subscription, 'order.created', ['id' => 1]);

    expect(fn () => $job->handle(app(WebhookService::class)))->toThrow(RuntimeException::class);

    $delivery = WebhookDelivery::query()->sole();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->response_code)->toBe(500)
        ->and($delivery->response_body_snippet)->toBe('Server Error')
        ->and($delivery->attempt_count)->toBe(1);
});

test('configures six attempts with the documented backoff delays', function () {
    $job = new DeliverWebhook(WebhookSubscription::factory()->create(['store_id' => $this->store->id]), 'order.created', []);

    expect($job->tries)->toBe(6)
        ->and($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);
});

test('marks the delivery as failed after max retries without throwing', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);

    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);

    $job = new DeliverWebhook($subscription, 'order.created', ['id' => 1]);
    $job->job = Mockery::mock(QueueJob::class, ['attempts' => 6]);

    // Final attempt: no exception, delivery is dead-lettered.
    $job->handle(app(WebhookService::class));

    $delivery = WebhookDelivery::query()->sole();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempt_count)->toBe(6);
});

test('pauses the subscription after five consecutive failures', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);
    Log::spy();

    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);

    foreach (range(1, 4) as $attempt) {
        runDelivery($subscription);
        expect($subscription->fresh()->status)->toBe(WebhookSubscriptionStatus::Active);
    }

    runDelivery($subscription);

    expect($subscription->fresh()->status)->toBe(WebhookSubscriptionStatus::Paused);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Webhook subscription paused after consecutive delivery failures', Mockery::type('array'));
});

test('a success resets the consecutive failure streak', function () {
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://fail.example.com/hook',
    ]);

    Http::fake([
        'fail.example.com/*' => Http::response('Server Error', 500),
        'ok.example.com/*' => Http::response('OK', 200),
    ]);

    foreach (range(1, 4) as $attempt) {
        runDelivery($subscription);
    }

    $subscription->forceFill(['target_url' => 'https://ok.example.com/hook'])->save();
    runDelivery($subscription->fresh());

    expect($subscription->fresh()->consecutiveFailures())->toBe(0);

    $subscription->forceFill(['target_url' => 'https://fail.example.com/hook'])->save();

    foreach (range(1, 4) as $attempt) {
        runDelivery($subscription->fresh());
    }

    // Streak is 4, not 8: the success reset the counter, so no pause yet.
    expect($subscription->fresh()->status)->toBe(WebhookSubscriptionStatus::Active);
});

test('paused subscriptions receive no further deliveries', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $subscription = WebhookSubscription::factory()->paused()->create(['store_id' => $this->store->id]);

    (new DeliverWebhook($subscription, 'order.created', ['id' => 1]))->handle(app(WebhookService::class));

    Http::assertNothingSent();
    expect(WebhookDelivery::query()->count())->toBe(0);
});
