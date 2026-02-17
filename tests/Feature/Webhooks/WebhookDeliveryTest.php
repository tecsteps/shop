<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Organization;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $org = Organization::factory()->create();
    $this->store = Store::factory()->for($org)->create();
    app()->instance('current_store', $this->store);
});

test('dispatch queues delivery jobs for matching subscriptions', function () {
    Queue::fake();

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    $service = new WebhookService;
    $service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class);
    $this->assertDatabaseHas('webhook_deliveries', [
        'status' => 'pending',
    ]);
});

test('dispatch ignores paused subscriptions', function () {
    Queue::fake();

    WebhookSubscription::factory()->paused()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
    ]);

    $service = new WebhookService;
    $service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNotPushed(DeliverWebhook::class);
});

test('deliver webhook records successful delivery', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 0,
    ]);

    $job = new DeliverWebhook($delivery->id, ['order_id' => 1]);
    $job->handle(new WebhookService);

    $delivery->refresh();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success);
    expect($delivery->response_code)->toBe(200);
    expect($delivery->attempt_count)->toBe(1);
});

test('deliver webhook records failed delivery', function () {
    Http::fake([
        '*' => Http::response('Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'attempt_count' => 5,
    ]);

    try {
        $job = new DeliverWebhook($delivery->id, ['order_id' => 1]);
        $job->handle(new WebhookService);
    } catch (\Exception) {
        // Expected release exception
    }

    $delivery->refresh();
    expect($delivery->response_code)->toBe(500);
    expect($delivery->attempt_count)->toBe(6);
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed);
});

test('dispatch does not match non-matching event types', function () {
    Queue::fake();

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'product.updated',
    ]);

    $service = new WebhookService;
    $service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNotPushed(DeliverWebhook::class);
});
