<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEventType;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('dispatch creates delivery records and queues matching active subscriptions', function (): void {
    Queue::fake([DeliverWebhook::class]);

    $store = Store::factory()->create();
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookEventType::OrderCreated,
        'status' => WebhookSubscriptionStatus::Active,
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookEventType::ProductUpdated,
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    app(WebhookService::class)->dispatch($store, WebhookEventType::OrderCreated->value, [
        'order' => ['id' => 10],
    ]);

    Queue::assertPushed(DeliverWebhook::class, function (DeliverWebhook $job) use ($subscription): bool {
        return $job->eventType === WebhookEventType::OrderCreated->value
            && WebhookDelivery::query()->whereKey($job->deliveryId)->where('subscription_id', $subscription->getKey())->exists();
    });
    Queue::assertPushed(DeliverWebhook::class, 1);

    expect($subscription->deliveries()->count())->toBe(1);
});

test('deliver webhook posts signed json payload and records success', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/webhooks/orders' => Http::response(['ok' => true], 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'target_url' => 'https://example.com/webhooks/orders',
        'signing_secret_encrypted' => 'whsec_test_secret',
    ]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Pending,
    ]);
    $payload = [
        'id' => $delivery->event_id,
        'api_version' => '2026-05',
        'event_type' => WebhookEventType::OrderCreated->value,
        'store_id' => $subscription->store_id,
        'data' => ['order' => ['id' => 10]],
    ];

    (new DeliverWebhook($delivery->getKey(), WebhookEventType::OrderCreated->value, $payload))
        ->handle(app(WebhookService::class));

    Http::assertSent(function ($request) use ($delivery, $subscription): bool {
        $timestamp = $request->header('X-Platform-Timestamp')[0] ?? '';
        $signature = $request->header('X-Platform-Signature')[0] ?? '';

        return $request->url() === 'https://example.com/webhooks/orders'
            && $request->header('X-Platform-Event')[0] === WebhookEventType::OrderCreated->value
            && $request->header('X-Platform-Delivery-Id')[0] === $delivery->event_id
            && app(WebhookService::class)->verify($timestamp.'.'.$request->body(), $signature, $subscription->fresh()->signing_secret_encrypted);
    });

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1);
});

test('failed deliveries pause subscription after five consecutive failures', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/webhooks/failing' => Http::response('nope', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'target_url' => 'https://example.com/webhooks/failing',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    WebhookDelivery::factory()->count(4)->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Failed,
        'last_attempt_at' => now()->subMinutes(5),
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Pending,
    ]);

    expect(fn () => (new DeliverWebhook($delivery->getKey(), WebhookEventType::OrderCreated->value, [
        'id' => $delivery->event_id,
        'data' => ['order' => ['id' => 10]],
    ]))->handle(app(WebhookService::class)))->toThrow(\RuntimeException::class);

    expect($subscription->refresh()->status)->toBe(WebhookSubscriptionStatus::Paused)
        ->and($delivery->refresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->response_code)->toBe(500);
});

test('delivery job uses the required retry schedule', function (): void {
    $job = new DeliverWebhook(1, WebhookEventType::OrderCreated->value, []);

    expect($job->tries)->toBe(6)
        ->and($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);
});
