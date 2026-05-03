<?php

use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
});

test('webhook service delivers signed json payloads to active subscriptions', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/webhooks/orders' => Http::response('ok', 200),
    ]);

    $subscription = WebhookSubscription::factory()
        ->for($this->store)
        ->create([
            'event_type' => 'order.created',
            'target_url' => 'https://example.com/webhooks/orders',
            'signing_secret_encrypted' => 'top-secret',
            'status' => 'active',
        ]);

    app(WebhookService::class)->dispatch($this->store, 'order.created', [
        'id' => 123,
        'order_number' => '#123',
    ]);

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();
        $timestamp = $request->header('X-Platform-Timestamp')[0] ?? '';
        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$body, 'top-secret');

        return $request->url() === 'https://example.com/webhooks/orders'
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('X-Platform-Event', 'order.created')
            && $request->hasHeader('X-Platform-Signature', $expectedSignature)
            && data_get(json_decode($body, true, flags: JSON_THROW_ON_ERROR), 'data.order_number') === '#123';
    });

    $delivery = WebhookDelivery::query()
        ->where('subscription_id', $subscription->id)
        ->firstOrFail();

    expect($delivery->status)->toBe('success')
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->response_code)->toBe(200)
        ->and($subscription->fresh()->consecutive_failures)->toBe(0);
});

test('failed webhook deliveries are recorded and pause repeated failures', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/webhooks/failing' => Http::response('unavailable', 503),
    ]);

    $subscription = WebhookSubscription::factory()
        ->for($this->store)
        ->create([
            'event_type' => 'order.paid',
            'target_url' => 'https://example.com/webhooks/failing',
            'signing_secret_encrypted' => 'top-secret',
            'status' => 'active',
            'consecutive_failures' => 4,
        ]);

    expect(fn () => app(WebhookService::class)->dispatch($this->store, 'order.paid', [
        'id' => 456,
        'order_number' => '#456',
    ]))->toThrow(\RuntimeException::class);

    $delivery = WebhookDelivery::query()
        ->where('subscription_id', $subscription->id)
        ->firstOrFail();

    expect($delivery->status)->toBe('failed')
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->response_code)->toBe(503)
        ->and($subscription->fresh()->consecutive_failures)->toBe(5)
        ->and($subscription->fresh()->status)->toBe('paused');
});
