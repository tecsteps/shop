<?php

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Queue;

it('signs and verifies webhook payload signatures', function () {
    $service = app(WebhookService::class);
    $payload = json_encode(['event' => 'order.created', 'id' => 1], JSON_THROW_ON_ERROR);
    $secret = 'webhook-secret';

    $signature = $service->sign($payload, $secret);

    expect($service->verify($payload, $signature, $secret))->toBeTrue()
        ->and($service->verify($payload, 'bad-signature', $secret))->toBeFalse();
});

it('dispatches webhook delivery jobs only for matching active subscriptions', function () {
    $store = Store::factory()->create();

    $active = WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/hook-active',
        'signing_secret_encrypted' => 'plain-secret',
        'status' => 'active',
    ]);

    WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.test/hook-paused',
        'signing_secret_encrypted' => 'plain-secret',
        'status' => 'paused',
    ]);

    WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'product.created',
        'target_url' => 'https://example.test/hook-other-event',
        'signing_secret_encrypted' => 'plain-secret',
        'status' => 'active',
    ]);

    Queue::fake();

    app(WebhookService::class)->dispatch($store, 'order.created', ['order_id' => 101]);

    Queue::assertPushed(DeliverWebhook::class, 1);
    Queue::assertPushed(DeliverWebhook::class, function (DeliverWebhook $job) use ($active): bool {
        return $job->subscriptionId === $active->id
            && $job->eventType === 'order.created'
            && $job->payload['order_id'] === 101;
    });
});
