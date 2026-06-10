<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Refund;
use App\Services\WebhookService;

/**
 * Translates domain events into outbound webhook event types (spec 05
 * sections 13.1 and 17). Product webhooks are dispatched from the
 * ProductObserver because product changes are model events, not domain
 * event classes.
 */
class DispatchWebhooks
{
    public function __construct(protected WebhookService $webhooks) {}

    public function handle(OrderCreated|OrderPaid|OrderFulfilled|OrderCancelled|OrderRefunded|CheckoutCompleted $event): void
    {
        match ($event::class) {
            OrderCreated::class => $this->dispatchOrderEvent('order.created', $event->order),
            OrderPaid::class => $this->dispatchOrderEvent('order.paid', $event->order),
            OrderFulfilled::class => $this->dispatchOrderEvent('order.fulfilled', $event->order),
            OrderCancelled::class => $this->dispatchOrderEvent('order.cancelled', $event->order),
            OrderRefunded::class => $this->dispatchOrderEvent('order.refunded', $event->order, $event->refund),
            CheckoutCompleted::class => $this->webhooks->dispatch(
                $event->order->store,
                'checkout.completed',
                $this->checkoutPayload($event),
            ),
        };
    }

    protected function dispatchOrderEvent(string $eventType, Order $order, ?Refund $refund = null): void
    {
        $payload = $this->orderPayload($order);

        if ($refund !== null) {
            $payload['refund'] = [
                'id' => $refund->getKey(),
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status?->value,
            ];
        }

        $this->webhooks->dispatch($order->store, $eventType, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(Order $order): array
    {
        return [
            'id' => $order->getKey(),
            'order_number' => $order->order_number,
            'status' => $order->status?->value,
            'financial_status' => $order->financial_status?->value,
            'fulfillment_status' => $order->fulfillment_status?->value,
            'payment_method' => $order->payment_method?->value,
            'currency' => $order->currency,
            'subtotal_amount' => $order->subtotal_amount,
            'discount_amount' => $order->discount_amount,
            'shipping_amount' => $order->shipping_amount,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'email' => $order->email,
            'customer_id' => $order->customer_id,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkoutPayload(CheckoutCompleted $event): array
    {
        return [
            'checkout_id' => $event->checkout->getKey(),
            'order_id' => $event->order->getKey(),
            'order_number' => $event->order->order_number,
            'email' => $event->order->email,
            'currency' => $event->order->currency,
            'total_amount' => $event->order->total_amount,
        ];
    }
}
