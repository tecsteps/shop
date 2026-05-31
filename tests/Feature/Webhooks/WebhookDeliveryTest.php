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

it('retries failed deliveries with exponential backoff', function () {
    Http::fake(['*' => Http::response('boom', 500)]);

    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    $job = new DeliverWebhook($delivery->id, 'order.created', ['id' => 1]);

    expect($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);

    expect(fn () => app()->call([$job, 'handle']))->toThrow(RuntimeException::class);

    expect($delivery->fresh()->attempt_count)->toBe(1)
        ->and($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Pending);
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
