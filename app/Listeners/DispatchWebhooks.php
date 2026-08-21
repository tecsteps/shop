<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Services\WebhookService;

class DispatchWebhooks
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function handle(object $event): void
    {
        [$store, $eventType, $resource] = match (true) {
            $event instanceof OrderCreated => [$event->order->store, 'order.created', $event->order],
            $event instanceof OrderPaid => [$event->order->store, 'order.paid', $event->order],
            $event instanceof OrderFulfilled => [$event->order->store, 'order.fulfilled', $event->order],
            $event instanceof OrderRefunded => [$event->order->store, 'order.refunded', $event->order],
            $event instanceof CheckoutCompleted => [$event->checkout->store, 'checkout.completed', $event->checkout],
            $event instanceof ProductCreated => [$event->product->store, 'product.created', $event->product],
            $event instanceof ProductDeleted => [$event->product->store, 'product.deleted', $event->product],
            $event instanceof ProductUpdated => [$event->product->store, $event->product->status->value === 'archived' ? 'product.deleted' : 'product.updated', $event->product],
            default => [null, null, null],
        };

        if ($store === null || $eventType === null || $resource === null) {
            return;
        }

        $this->webhooks->dispatch($store, $eventType, [
            'api_version' => '2026-01',
            'id' => (string) $resource->getKey(),
            'type' => $eventType,
            'data' => $resource->toArray(),
        ]);
    }
}
