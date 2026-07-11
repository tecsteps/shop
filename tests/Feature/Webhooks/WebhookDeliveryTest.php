<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('dispatch creates deliveries and queues jobs for matching active subscriptions', function () {
    Queue::fake();
    $store = Store::factory()->create();
    WebhookSubscription::factory()->for($store)->create(['event_type' => 'order.created']);
    WebhookSubscription::factory()->for($store)->create(['event_type' => 'order.refunded']);

    app(WebhookService::class)->dispatch($store, 'order.created', ['order_id' => 42]);

    expect(WebhookDelivery::query()->count())->toBe(1);
    Queue::assertPushed(DeliverWebhook::class, function (DeliverWebhook $job): bool {
        return $job->payload['type'] === 'order.created'
            && $job->payload['data'] === ['order_id' => 42];
    });
});

test('delivery job posts signed json headers and records a successful response', function () {
    Http::preventStrayRequests();
    Http::fake(['https://hooks.example.test/*' => Http::response('accepted', 202)]);
    $subscription = WebhookSubscription::factory()->create([
        'target_url' => 'https://hooks.example.test/orders',
        'event_type' => 'order.created',
        'signing_secret_encrypted' => 'secret',
    ]);
    $delivery = WebhookDelivery::factory()->for($subscription, 'subscription')->create([
        'event_id' => 'delivery-uuid',
    ]);
    $payload = ['id' => 'delivery-uuid', 'type' => 'order.created', 'data' => ['order_id' => 42]];
    $timestamp = 1783764000;

    (new DeliverWebhook($delivery->id, $payload, $timestamp))->handle(app(WebhookService::class));

    $delivery->refresh();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(202)
        ->and($delivery->response_body_snippet)->toBe('accepted');

    Http::assertSent(function (Request $request) use ($timestamp): bool {
        return $request->url() === 'https://hooks.example.test/orders'
            && $request->hasHeader('X-Platform-Event', 'order.created')
            && $request->hasHeader('X-Platform-Delivery-Id', 'delivery-uuid')
            && $request->hasHeader('X-Platform-Timestamp', (string) $timestamp)
            && $request->hasHeader('X-Platform-Signature');
    });
});

test('five consecutive failed deliveries pause the subscription', function () {
    Http::preventStrayRequests();
    Http::fake(['https://hooks.example.test/*' => Http::response('down', 500)]);
    $subscription = WebhookSubscription::factory()->create([
        'target_url' => 'https://hooks.example.test/orders',
    ]);
    WebhookDelivery::factory()->count(4)->for($subscription, 'subscription')->create([
        'status' => WebhookDeliveryStatus::Failed,
    ]);
    $delivery = WebhookDelivery::factory()->for($subscription, 'subscription')->create();
    $job = new DeliverWebhook($delivery->id, ['type' => 'order.created'], 1783764000);

    expect(fn () => $job->handle(app(WebhookService::class)))
        ->toThrow(RequestException::class);

    expect($subscription->refresh()->status)->toBe(WebhookSubscriptionStatus::Paused)
        ->and($delivery->refresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);
});
