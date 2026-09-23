<?php

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('queues active matching subscriptions and signs the exact JSON payload', function () {
    $store = shopStore();
    $subscription = WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://hooks.example.test/events',
        'signing_secret_encrypted' => 'subscription-secret',
        'status' => 'active',
    ]);
    WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://paused.example.test/events',
        'signing_secret_encrypted' => 'paused-secret',
        'status' => 'paused',
    ]);
    Queue::fake();
    $service = app(WebhookService::class);

    expect($service->dispatch($store, 'order.created', ['order_id' => 42], new DateTimeImmutable('2026-04-12T10:11:12Z')))->toBe(1);
    Queue::assertPushed(DeliverWebhook::class, fn (DeliverWebhook $job): bool => $job->subscriptionId === $subscription->id
        && $job->eventType === 'order.created'
        && $job->timestamp === 1775988672);

    Http::fake(['https://hooks.example.test/*' => Http::response('accepted', 202)]);
    $service->deliver($subscription->id, 'delivery-event-1', 'order.created', ['order_id' => 42], 1775988672);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://hooks.example.test/events'
        && $request->header('X-Platform-Event')[0] === 'order.created'
        && $request->header('X-Platform-Timestamp')[0] === '1775988672'
        && $request->header('X-Platform-Signature')[0] === hash_hmac('sha256', '{"order_id":42}', 'subscription-secret'));
    $delivery = WebhookDelivery::query()->where('event_id', 'delivery-event-1')->firstOrFail();
    expect($delivery->status)->toBe('success')
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->response_code)->toBe(202)
        ->and($delivery->response_body_snippet)->toBe('accepted');
});

it('pauses a webhook subscription after five consecutive failed attempts', function () {
    $store = shopStore();
    $subscription = WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'product.updated',
        'target_url' => 'https://hooks.example.test/fail',
        'signing_secret_encrypted' => 'subscription-secret',
        'status' => 'active',
    ]);
    Http::fake(['https://hooks.example.test/*' => Http::response('unavailable', 503)]);
    $service = app(WebhookService::class);

    foreach (range(1, 5) as $attempt) {
        expect(fn () => $service->deliver($subscription->id, 'delivery-event-2', 'product.updated', ['product_id' => 7], 1775988672))
            ->toThrow(RuntimeException::class);
    }

    expect($subscription->refresh()->status)->toBe('paused')
        ->and(WebhookDelivery::query()->where('event_id', 'delivery-event-2')->value('attempt_count'))->toBe(5)
        ->and(WebhookDelivery::query()->where('event_id', 'delivery-event-2')->value('response_code'))->toBe(503);
});
