<?php

use App\Enums\WebhookTopic;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;
use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('enqueues a DeliverWebhook job for each active matching subscription', function () {
    Queue::fake();

    $store = Store::factory()->create();
    WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookTopic::OrderPaid->value,
        'status' => 'active',
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookTopic::OrderPaid->value,
        'status' => 'paused',
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookTopic::OrderCreated->value,
        'status' => 'active',
    ]);

    $count = app(WebhookDispatcher::class)->dispatch(WebhookTopic::OrderPaid, (int) $store->getKey(), ['order_id' => 1]);

    expect($count)->toBe(1);
    Queue::assertPushed(DeliverWebhook::class, 1);
});

it('scopes dispatch to the given store', function () {
    Queue::fake();

    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    WebhookSubscription::factory()->create([
        'store_id' => $storeA->getKey(),
        'event_type' => WebhookTopic::OrderPaid->value,
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $storeB->getKey(),
        'event_type' => WebhookTopic::OrderPaid->value,
    ]);

    app(WebhookDispatcher::class)->dispatch(WebhookTopic::OrderPaid, (int) $storeA->getKey(), []);

    Queue::assertPushed(DeliverWebhook::class, 1);
});
