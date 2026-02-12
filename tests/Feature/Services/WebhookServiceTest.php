<?php

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Queue;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('dispatch creates delivery records for matching subscriptions', function () {
    Queue::fake();

    $store = Store::factory()->create();

    $sub1 = WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'topic' => 'orders/create',
        'is_active' => true,
    ]);

    $sub2 = WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'topic' => 'orders/create',
        'is_active' => true,
    ]);

    $service = new WebhookService;
    $service->dispatch($store, 'orders/create', ['id' => 1, 'total' => 5000]);

    expect(WebhookDelivery::count())->toBe(2);

    $delivery1 = WebhookDelivery::where('webhook_subscription_id', $sub1->id)->first();
    expect($delivery1)->not->toBeNull()
        ->and($delivery1->event_type)->toBe('orders/create')
        ->and(json_decode($delivery1->payload_json, true))->toBe(['id' => 1, 'total' => 5000]);

    $delivery2 = WebhookDelivery::where('webhook_subscription_id', $sub2->id)->first();
    expect($delivery2)->not->toBeNull();

    Queue::assertPushed(DeliverWebhook::class, 2);
});

test('dispatch ignores inactive subscriptions', function () {
    Queue::fake();

    $store = Store::factory()->create();

    WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'topic' => 'orders/create',
        'is_active' => false,
    ]);

    $service = new WebhookService;
    $service->dispatch($store, 'orders/create', ['id' => 1]);

    expect(WebhookDelivery::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('dispatch ignores non-matching topics', function () {
    Queue::fake();

    $store = Store::factory()->create();

    WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'topic' => 'products/create',
        'is_active' => true,
    ]);

    $service = new WebhookService;
    $service->dispatch($store, 'orders/create', ['id' => 1]);

    expect(WebhookDelivery::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('sign generates correct HMAC-SHA256 signature', function () {
    $service = new WebhookService;

    $payload = '{"id":1}';
    $secret = 'test-secret';

    $signature = $service->sign($payload, $secret);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret));
});

test('verify returns true for valid signature', function () {
    $service = new WebhookService;

    $payload = '{"id":1}';
    $secret = 'test-secret';
    $signature = $service->sign($payload, $secret);

    expect($service->verify($payload, $signature, $secret))->toBeTrue();
});

test('verify returns false for invalid signature', function () {
    $service = new WebhookService;

    $payload = '{"id":1}';
    $secret = 'test-secret';

    expect($service->verify($payload, 'invalid-signature', $secret))->toBeFalse();
});

test('dispatch updates last_triggered_at on subscription', function () {
    Queue::fake();

    $store = Store::factory()->create();

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->id,
        'topic' => 'orders/create',
        'is_active' => true,
        'last_triggered_at' => null,
    ]);

    $service = new WebhookService;
    $service->dispatch($store, 'orders/create', ['id' => 1]);

    $subscription->refresh();
    expect($subscription->last_triggered_at)->not->toBeNull();
});
