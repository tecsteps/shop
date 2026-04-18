<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Services\WebhookService;

class DispatchWebhooks
{
    public function __construct(protected WebhookService $webhooks) {}

    public function onOrderCreated(OrderCreated $event): void
    {
        $this->dispatch('order.created', $event->order);
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $this->dispatch('order.paid', $event->order);
    }

    public function onOrderFulfilled(OrderFulfilled $event): void
    {
        $this->dispatch('order.fulfilled', $event->order);
    }

    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->dispatch('order.cancelled', $event->order);
    }

    public function onOrderRefunded(OrderRefunded $event): void
    {
        $this->dispatch('order.refunded', $event->order);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            OrderCreated::class => 'onOrderCreated',
            OrderPaid::class => 'onOrderPaid',
            OrderFulfilled::class => 'onOrderFulfilled',
            OrderCancelled::class => 'onOrderCancelled',
            OrderRefunded::class => 'onOrderRefunded',
        ];
    }

    protected function dispatch(string $eventType, Order $order): void
    {
        $this->webhooks->dispatchEvent($order->store_id, $eventType, [
            'type' => $eventType,
            'occurred_at' => now()->toIso8601String(),
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $order->status->value,
                'financial_status' => $order->financial_status->value,
            ],
        ]);
    }
}
