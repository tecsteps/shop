<?php

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(WebhookService::class);
});

test('dispatches webhook to matching subscriptions', function () {
    Queue::fake();

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.created', 'order.paid'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => true,
    ]);

    $this->service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class, function ($job) {
        return $job->eventType === 'order.created'
            && $job->payload['order_id'] === 1;
    });
});

test('skips inactive subscriptions', function () {
    Queue::fake();

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.created'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => false,
    ]);

    $this->service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});

test('skips subscriptions that dont match event type', function () {
    Queue::fake();

    WebhookSubscription::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/webhook',
        'event_types_json' => ['order.paid'],
        'signing_secret_encrypted' => 'test-secret',
        'is_active' => true,
    ]);

    $this->service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});

test('generates valid HMAC signature', function () {
    $payload = '{"order_id":1}';
    $secret = 'test-secret-key';
    $timestamp = time();

    $signature = $this->service->signWithTimestamp($payload, $secret, $timestamp);

    $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    expect($signature)->toBe($expected);
});

test('verifies valid signature', function () {
    $payload = '{"order_id":1}';
    $secret = 'test-secret-key';
    $timestamp = (string) time();

    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    $result = $this->service->verify($payload, $signature, $timestamp, $secret);

    expect($result)->toBeTrue();
});

test('rejects tampered signature', function () {
    $payload = '{"order_id":1}';
    $secret = 'test-secret-key';
    $timestamp = (string) time();

    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    // Tamper with the payload
    $result = $this->service->verify('{"order_id":2}', $signature, $timestamp, $secret);

    expect($result)->toBeFalse();
});
