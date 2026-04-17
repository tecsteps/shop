<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Services\WebhookService;

class DispatchOrderWebhooks
{
    public function __construct(private readonly WebhookService $webhookService) {}

    public function handleCreated(OrderCreated $event): void
    {
        $this->dispatch('order.created', $event->order);
    }

    public function handlePaid(OrderPaid $event): void
    {
        $this->dispatch('order.paid', $event->order);
    }

    public function handleFulfilled(OrderFulfilled $event): void
    {
        $this->dispatch('order.fulfilled', $event->order);
    }

    private function dispatch(string $eventType, Order $order): void
    {
        $this->webhookService->dispatch($order->store, $eventType, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
        ]);
    }
}
