<?php

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('dispatches a delivery job for each active subscription', function (): void {
    Queue::fake();

    WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'status' => 'active',
    ]);
    WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'status' => 'active',
    ]);
    WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'status' => 'paused',
    ]);
    WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.paid',
        'status' => 'active',
    ]);

    app(WebhookService::class)->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class, 2);
});

it('records a successful delivery row', function (): void {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $subscription = WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'url' => 'https://example.test/webhooks/orders',
        'secret' => 'shh',
        'status' => 'active',
    ]);

    (new DeliverWebhook($subscription, 'order.created', ['order_id' => 1]))
        ->handle(app(WebhookService::class));

    $delivery = WebhookDelivery::query()->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->event_type)->toBe('order.created')
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->delivered_at)->not->toBeNull();

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event', 'order.created')
            && $request->hasHeader('X-Platform-Delivery-Id')
            && $request->hasHeader('X-Platform-Timestamp');
    });
});

it('pauses a subscription after five consecutive failures', function (): void {
    Http::fake([
        '*' => Http::response('server down', 500),
    ]);

    $subscription = WebhookSubscription::factory()->for($this->store)->create([
        'event_type' => 'order.created',
        'url' => 'https://example.test/broken',
        'secret' => 'shh',
        'status' => 'active',
        'failed_count' => 4,
    ]);

    try {
        (new DeliverWebhook($subscription, 'order.created', ['x' => 1]))
            ->handle(app(WebhookService::class));
    } catch (\Throwable $exception) {
        // expected failure from non-2xx response
    }

    $subscription->refresh();

    expect($subscription->failed_count)->toBe(5)
        ->and($subscription->status)->toBe('paused');
});
