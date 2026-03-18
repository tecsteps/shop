<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Services\WebhookService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attribute\AsEventListener;

#[AsEventListener(event: OrderCreated::class)]
#[AsEventListener(event: OrderPaid::class)]
#[AsEventListener(event: OrderFulfilled::class)]
#[AsEventListener(event: OrderCancelled::class)]
#[AsEventListener(event: OrderRefunded::class)]
class DispatchOrderWebhooks implements ShouldHandleEventsAfterCommit
{
    public function __construct(private WebhookService $webhookService) {}

    public function handle(OrderCreated|OrderPaid|OrderFulfilled|OrderCancelled|OrderRefunded $event): void
    {
        $order = $event->order;
        $store = $order->store;

        if (! $store) {
            return;
        }

        $eventType = match (true) {
            $event instanceof OrderCreated => 'order.created',
            $event instanceof OrderPaid => 'order.paid',
            $event instanceof OrderFulfilled => 'order.fulfilled',
            $event instanceof OrderCancelled => 'order.cancelled',
            $event instanceof OrderRefunded => 'order.refunded',
        };

        $this->webhookService->dispatch($store, $eventType, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }
}
