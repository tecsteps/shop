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
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Services\WebhookService;

/**
 * Maps domain events to webhook event types and queues deliveries via
 * the WebhookService (spec 05 §13.1/§13.2).
 */
class DispatchWebhooks
{
    /**
     * Domain event class => webhook event type (spec 05 §13.1).
     *
     * @var array<class-string, string>
     */
    public const EVENT_MAP = [
        OrderCreated::class => 'order.created',
        OrderPaid::class => 'order.paid',
        OrderFulfilled::class => 'order.fulfilled',
        OrderRefunded::class => 'order.refunded',
        ProductCreated::class => 'product.created',
        ProductUpdated::class => 'product.updated',
        ProductDeleted::class => 'product.deleted',
        CheckoutCompleted::class => 'checkout.completed',
    ];

    public function __construct(private WebhookService $webhooks) {}

    /**
     * Dispatch a webhook for each active subscription of the event's store.
     */
    public function handle(object $event): void
    {
        $eventType = self::EVENT_MAP[$event::class] ?? null;

        if ($eventType === null) {
            return;
        }

        [$store, $payload] = match (true) {
            $event instanceof OrderCreated, $event instanceof OrderPaid, $event instanceof OrderFulfilled => [
                $event->order->store,
                $this->orderPayload($event->order),
            ],
            $event instanceof OrderRefunded => [
                $event->order->store,
                $this->orderPayload($event->order) + [
                    'refund' => [
                        'id' => $event->refund->id,
                        'amount' => $event->refund->amount,
                        'reason' => $event->refund->reason,
                    ],
                ],
            ],
            $event instanceof ProductCreated, $event instanceof ProductUpdated, $event instanceof ProductDeleted => [
                $event->product->store,
                $this->productPayload($event->product),
            ],
            $event instanceof CheckoutCompleted => [
                $event->checkout->store,
                $this->checkoutPayload($event->checkout),
            ],
            default => [null, null],
        };

        if ($store === null || $payload === null) {
            return;
        }

        $this->webhooks->dispatch($store, $eventType, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'fulfillment_status' => $order->fulfillment_status->value,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'title' => $product->title,
            'handle' => $product->handle,
            'status' => $product->status->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(Checkout $checkout): array
    {
        return [
            'id' => $checkout->id,
            'status' => $checkout->status->value,
            'email' => $checkout->email,
        ];
    }
}
