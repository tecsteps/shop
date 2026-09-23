<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductStatusChanged;
use App\Events\ProductUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchWebhooks
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function orderCreated(OrderCreated $event): void
    {
        $this->dispatchOrder($event->order, 'order.created');
    }

    public function orderPaid(OrderPaid $event): void
    {
        $this->dispatchOrder($event->order, 'order.paid');
    }

    public function checkoutCompleted(CheckoutCompleted $event): void
    {
        $this->dispatchOrder($event->order, 'checkout.completed');
    }

    public function orderFulfilled(OrderFulfilled $event): void
    {
        $this->dispatchOrder($event->order, 'order.fulfilled');
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        $this->dispatchOrder($event->order, 'order.cancelled');
    }

    public function orderRefunded(OrderRefunded $event): void
    {
        $order = $event->order;
        $this->dispatchOrder($order, 'order.refunded', [
            'refund' => [
                'id' => $event->refund->getKey(),
                'amount' => (int) $event->refund->amount,
                'currency' => $order->currency,
                'reason' => $event->refund->reason,
                'status' => $event->refund->status,
            ],
        ]);
    }

    public function productCreated(ProductCreated $event): void
    {
        $this->dispatchProduct($event->product, 'product.created');
    }

    public function productUpdated(ProductUpdated $event): void
    {
        $this->dispatchProduct($event->product, 'product.updated');
    }

    public function productDeleted(ProductDeleted $event): void
    {
        $store = Store::query()->findOrFail($event->storeId);
        $this->webhooks->dispatch($store, 'product.deleted', $event->product);
    }

    public function productStatusChanged(ProductStatusChanged $event): void
    {
        if ($event->newlyCreated) {
            return;
        }

        $eventType = $event->newStatus === 'archived' ? 'product.deleted' : 'product.updated';
        $this->dispatchProduct($event->product, $eventType);
    }

    public function fulfillmentShipped(FulfillmentShipped $event): void
    {
        $order = $event->fulfillment->order;

        if ($order?->fulfillment_status === 'fulfilled') {
            $this->dispatchOrder($order, 'order.fulfilled');
        }
    }

    public function fulfillmentDelivered(FulfillmentDelivered $event): void
    {
        $order = $event->fulfillment->order;

        if ($order?->fulfillment_status === 'fulfilled') {
            $this->dispatchOrder($order, 'order.fulfilled');
        }
    }

    /** @param array<string, mixed> $extra */
    private function dispatchOrder(Order $order, string $eventType, array $extra = []): void
    {
        $order->loadMissing('lines');
        $payload = [
            'id' => $order->getKey(),
            'store_id' => $order->store_id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'currency' => $order->currency,
            'subtotal_amount' => (int) $order->subtotal_amount,
            'discount_amount' => (int) $order->discount_amount,
            'shipping_amount' => (int) $order->shipping_amount,
            'tax_amount' => (int) $order->tax_amount,
            'total_amount' => (int) $order->total_amount,
            'line_items' => $order->lines->map(static fn ($line): array => [
                'id' => $line->getKey(),
                'product_id' => $line->product_id,
                'variant_id' => $line->variant_id,
                'title' => $line->title_snapshot,
                'variant_title' => $line->variant_title_snapshot,
                'sku' => $line->sku_snapshot,
                'quantity' => (int) $line->quantity,
                'unit_price_amount' => (int) $line->unit_price_amount,
                'total_amount' => (int) $line->total_amount,
            ])->all(),
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];

        $this->webhooks->dispatch(
            Store::query()->findOrFail($order->store_id),
            $eventType,
            array_replace($payload, $extra),
        );
    }

    private function dispatchProduct(Product $product, string $eventType): void
    {
        $this->webhooks->dispatch(Store::query()->findOrFail($product->store_id), $eventType, [
            'id' => $product->getKey(),
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'status' => $product->status,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'tags' => $product->tags,
            'published_at' => $product->published_at?->toIso8601String(),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ]);
    }
}
