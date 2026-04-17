<?php

namespace App\Listeners;

use App\Enums\WebhookTopic;
use App\Events\CustomerCreated;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Services\WebhookDispatcher;

class DispatchWebhooks
{
    public function __construct(private readonly WebhookDispatcher $dispatcher) {}

    public function handleOrderCreated(OrderCreated $event): void
    {
        $this->dispatcher->dispatch(
            WebhookTopic::OrderCreated,
            (int) $event->order->store_id,
            $this->orderPayload($event->order),
        );
    }

    public function handleOrderPaid(OrderPaid $event): void
    {
        $this->dispatcher->dispatch(
            WebhookTopic::OrderPaid,
            (int) $event->order->store_id,
            $this->orderPayload($event->order),
        );
    }

    public function handleOrderCancelled(OrderCancelled $event): void
    {
        $this->dispatcher->dispatch(
            WebhookTopic::OrderCancelled,
            (int) $event->order->store_id,
            $this->orderPayload($event->order),
        );
    }

    public function handleOrderRefunded(OrderRefunded $event): void
    {
        $this->dispatcher->dispatch(
            WebhookTopic::OrderRefunded,
            (int) $event->order->store_id,
            array_merge($this->orderPayload($event->order), [
                'refund_id' => $event->refund->getKey(),
                'refund_amount' => (int) $event->refund->amount,
            ]),
        );
    }

    public function handleOrderFulfilled(OrderFulfilled $event): void
    {
        $this->dispatcher->dispatch(
            WebhookTopic::OrderFulfilled,
            (int) $event->order->store_id,
            $this->orderPayload($event->order),
        );
    }

    public function handleFulfillmentCreated(FulfillmentCreated $event): void
    {
        $fulfillment = $event->fulfillment->load('order');
        $this->dispatcher->dispatch(
            WebhookTopic::FulfillmentCreated,
            (int) ($fulfillment->order?->store_id ?? 0),
            [
                'fulfillment_id' => $fulfillment->getKey(),
                'order_id' => $fulfillment->order_id,
                'status' => $fulfillment->status?->value,
            ],
        );
    }

    public function handleFulfillmentShipped(FulfillmentShipped $event): void
    {
        $fulfillment = $event->fulfillment->load('order');
        $this->dispatcher->dispatch(
            WebhookTopic::FulfillmentShipped,
            (int) ($fulfillment->order?->store_id ?? 0),
            [
                'fulfillment_id' => $fulfillment->getKey(),
                'order_id' => $fulfillment->order_id,
                'tracking_number' => $fulfillment->tracking_number,
            ],
        );
    }

    public function handleCustomerCreated(CustomerCreated $event): void
    {
        $this->dispatcher->dispatch(
            WebhookTopic::CustomerCreated,
            (int) $event->customer->store_id,
            [
                'customer_id' => $event->customer->getKey(),
                'email' => $event->customer->email,
                'name' => $event->customer->name,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(\App\Models\Order $order): array
    {
        return [
            'order_id' => $order->getKey(),
            'order_number' => $order->order_number,
            'status' => $order->status?->value,
            'financial_status' => $order->financial_status?->value,
            'fulfillment_status' => $order->fulfillment_status?->value,
            'total_amount' => (int) $order->total_amount,
            'currency' => $order->currency,
        ];
    }
}
