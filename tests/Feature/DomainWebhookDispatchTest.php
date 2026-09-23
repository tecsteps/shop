<?php

use App\Events\OrderCreated;
use App\Events\ProductStatusChanged;
use App\Jobs\DeliverWebhook;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Queue;

it('queues a tenant-scoped order webhook with safe order details', function () {
    $store = shopStore();
    $otherStore = shopStore('webhook-tenant-other.test');
    $subscription = WebhookSubscription::create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://hooks.example.test/orders',
        'signing_secret_encrypted' => 'test-signing-secret',
        'status' => 'active',
    ]);
    WebhookSubscription::create([
        'store_id' => $otherStore->id,
        'event_type' => 'order.created',
        'target_url' => 'https://hooks.other.test/orders',
        'signing_secret_encrypted' => 'other-secret',
        'status' => 'active',
    ]);
    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#1001',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'EUR',
        'total_amount' => 2500,
        'email' => 'private@example.test',
        'placed_at' => now(),
    ]);
    $line = $order->lines()->create([
        'title_snapshot' => 'Blue shirt',
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'total_amount' => 2500,
    ]);
    Queue::fake([DeliverWebhook::class]);

    $listener = app('events')->getListeners(OrderCreated::class)[0];
    $event = new OrderCreated($order);
    $listener($event, [$event]);

    Queue::assertPushedTimes(DeliverWebhook::class, 1);
    Queue::assertPushed(DeliverWebhook::class, fn (DeliverWebhook $job): bool => $job->subscriptionId === $subscription->id
        && $job->eventType === 'order.created'
        && $job->payload['id'] === $order->id
        && $job->payload['line_items'][0]['id'] === $line->id
        && ! array_key_exists('email', $job->payload));
});

it('maps archiving a product to the canonical deleted event and skips new product status noise', function () {
    $store = shopStore();
    $subscription = WebhookSubscription::create([
        'store_id' => $store->id,
        'event_type' => 'product.deleted',
        'target_url' => 'https://hooks.example.test/products',
        'signing_secret_encrypted' => 'test-signing-secret',
        'status' => 'active',
    ]);
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'archived']);
    Queue::fake([DeliverWebhook::class]);
    $listener = app(\App\Listeners\DispatchWebhooks::class);

    $listener->productStatusChanged(new ProductStatusChanged($product, 'active', 'archived'));
    $listener->productStatusChanged(new ProductStatusChanged($product, 'draft', 'active', true));

    Queue::assertPushedTimes(DeliverWebhook::class, 1);
    Queue::assertPushed(DeliverWebhook::class, fn (DeliverWebhook $job): bool => $job->subscriptionId === $subscription->id
        && $job->eventType === 'product.deleted'
        && $job->payload['id'] === $product->id);
});
