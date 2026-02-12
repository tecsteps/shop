<?php

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('successful delivery marks delivered_at and resets failure count', function () {
    Http::fake([
        'example.com/*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'secret' => 'test-secret',
        'is_active' => true,
        'failure_count' => 2,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_subscription_id' => $subscription->id,
        'event_type' => 'orders/create',
        'payload_json' => json_encode(['id' => 1]),
    ]);

    (new DeliverWebhook($delivery))->handle(new \App\Services\WebhookService);

    $delivery->refresh();
    $subscription->refresh();

    expect($delivery->delivered_at)->not->toBeNull()
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->attempts)->toBe(1)
        ->and($subscription->failure_count)->toBe(0);
});

test('failed delivery increments failure count', function () {
    Http::fake([
        'example.com/*' => Http::response('Server Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'secret' => 'test-secret',
        'is_active' => true,
        'failure_count' => 0,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_subscription_id' => $subscription->id,
    ]);

    (new DeliverWebhook($delivery))->handle(new \App\Services\WebhookService);

    $delivery->refresh();
    $subscription->refresh();

    expect($delivery->delivered_at)->toBeNull()
        ->and($delivery->response_status)->toBe(500)
        ->and($subscription->failure_count)->toBe(1)
        ->and($delivery->next_retry_at)->not->toBeNull();
});

test('circuit breaker deactivates subscription after 5 consecutive failures', function () {
    Http::fake([
        'example.com/*' => Http::response('Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'secret' => 'test-secret',
        'is_active' => true,
        'failure_count' => 4,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_subscription_id' => $subscription->id,
    ]);

    (new DeliverWebhook($delivery))->handle(new \App\Services\WebhookService);

    $subscription->refresh();
    $delivery->refresh();

    expect($subscription->is_active)->toBeFalse()
        ->and($subscription->failure_count)->toBe(5)
        ->and($delivery->failed_at)->not->toBeNull();
});

test('delivery sends correct headers including signature', function () {
    Http::fake([
        'example.com/*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'secret' => 'webhook-secret',
        'is_active' => true,
    ]);

    $payload = json_encode(['id' => 42]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_subscription_id' => $subscription->id,
        'event_type' => 'products/update',
        'payload_json' => $payload,
    ]);

    (new DeliverWebhook($delivery))->handle(new \App\Services\WebhookService);

    Http::assertSent(function ($request) use ($delivery) {
        return $request->hasHeader('X-Platform-Event', 'products/update')
            && $request->hasHeader('X-Platform-Delivery-Id', (string) $delivery->id)
            && $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Timestamp');
    });
});

test('delivery skips if subscription is inactive', function () {
    Http::fake();

    $subscription = WebhookSubscription::factory()->create([
        'is_active' => false,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_subscription_id' => $subscription->id,
    ]);

    (new DeliverWebhook($delivery))->handle(new \App\Services\WebhookService);

    Http::assertNothingSent();

    $delivery->refresh();
    expect($delivery->attempts)->toBe(0);
});

test('delivery handles connection exceptions gracefully', function () {
    Http::fake([
        'example.com/*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'secret' => 'test-secret',
        'is_active' => true,
        'failure_count' => 0,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_subscription_id' => $subscription->id,
    ]);

    (new DeliverWebhook($delivery))->handle(new \App\Services\WebhookService);

    $delivery->refresh();
    $subscription->refresh();

    expect($subscription->failure_count)->toBe(1)
        ->and($delivery->response_body)->toContain('Connection refused');
});
