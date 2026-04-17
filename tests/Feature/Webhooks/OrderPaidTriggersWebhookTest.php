<?php

use App\Enums\WebhookTopic;
use App\Events\OrderPaid;
use App\Jobs\DeliverWebhook;
use App\Models\Order;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('enqueues a webhook job when the OrderPaid event fires', function () {
    Queue::fake();

    $store = Store::factory()->create();
    $order = Order::factory()->paid()->create(['store_id' => $store->getKey()]);

    WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookTopic::OrderPaid->value,
        'status' => 'active',
    ]);

    OrderPaid::dispatch($order);

    Queue::assertPushed(DeliverWebhook::class, function ($job) use ($order): bool {
        return $job->topic === WebhookTopic::OrderPaid->value
            && $job->payload['order_id'] === (int) $order->getKey();
    });
});
