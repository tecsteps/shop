<?php

namespace App\Listeners;

use App\Enums\FulfillmentStatus;
use App\Enums\ProductStatus;
use App\Events\CheckoutCompleted;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductStatusChanged;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Product;
use App\Services\WebhookService;
use Illuminate\Events\Dispatcher;

/**
 * Fan out domain events to webhook subscriptions per spec 05 section 13.2.
 */
class DispatchWebhooks
{
    public function __construct(protected WebhookService $webhooks) {}

    /**
     * @param  array<int, class-string>  $events
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderCreated::class => 'onOrderCreated',
            OrderPaid::class => 'onOrderPaid',
            OrderRefunded::class => 'onOrderRefunded',
            OrderCancelled::class => 'onOrderCancelled',
            CheckoutCompleted::class => 'onCheckoutCompleted',
            FulfillmentCreated::class => 'onFulfillmentCreated',
            FulfillmentShipped::class => 'onFulfillmentShipped',
            FulfillmentDelivered::class => 'onFulfillmentDelivered',
            ProductStatusChanged::class => 'onProductStatusChanged',
        ];
    }

    public function onOrderCreated(OrderCreated $event): void
    {
        $order = $event->order;

        $this->webhooks->dispatch($order->store, 'order.created', $this->orderPayload($order));
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $order = $event->order;

        $this->webhooks->dispatch($order->store, 'order.paid', $this->orderPayload($order));
        $this->webhooks->dispatch($order->store, 'order.updated', $this->orderPayload($order));
    }

    public function onOrderRefunded(OrderRefunded $event): void
    {
        $order = $event->order;

        $this->webhooks->dispatch($order->store, 'order.refunded', array_merge(
            $this->orderPayload($order),
            ['refund' => [
                'id' => $event->refund->getKey(),
                'amount' => $event->refund->amount,
                'reason' => $event->refund->reason,
            ]]
        ));
        $this->webhooks->dispatch($order->store, 'refund.created', [
            'id' => $event->refund->getKey(),
            'order_id' => $order->getKey(),
            'amount' => $event->refund->amount,
        ]);
    }

    public function onOrderCancelled(OrderCancelled $event): void
    {
        $order = $event->order;

        $this->webhooks->dispatch($order->store, 'order.cancelled', $this->orderPayload($order));
        $this->webhooks->dispatch($order->store, 'order.updated', $this->orderPayload($order));
    }

    public function onCheckoutCompleted(CheckoutCompleted $event): void
    {
        $order = $event->order;

        $this->webhooks->dispatch($order->store, 'checkout.completed', [
            'checkout_id' => $event->checkout->getKey(),
            'order_id' => $order->getKey(),
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
        ]);
    }

    public function onFulfillmentCreated(FulfillmentCreated $event): void
    {
        $fulfillment = $event->fulfillment;
        $order = $fulfillment->order;

        $this->webhooks->dispatch($order->store, 'fulfillment.created', $this->fulfillmentPayload($fulfillment));
    }

    public function onFulfillmentShipped(FulfillmentShipped $event): void
    {
        $this->dispatchFulfillmentStatus($event->fulfillment);
    }

    public function onFulfillmentDelivered(FulfillmentDelivered $event): void
    {
        $this->dispatchFulfillmentStatus($event->fulfillment);
    }

    public function onProductStatusChanged(ProductStatusChanged $event): void
    {
        $product = $event->product;

        $eventType = match (true) {
            $event->to === ProductStatus::Archived => 'product.deleted',
            $event->from === ProductStatus::Draft && $event->to === ProductStatus::Active => 'product.created',
            default => 'product.updated',
        };

        $this->webhooks->dispatch($product->store, $eventType, $this->productPayload($product));
    }

    public function dispatchCustomerCreated(Customer $customer): void
    {
        $this->webhooks->dispatch($customer->store, 'customer.created', [
            'id' => $customer->getKey(),
            'email' => $customer->email,
            'name' => $customer->name,
        ]);
    }

    protected function dispatchFulfillmentStatus(Fulfillment $fulfillment): void
    {
        $order = $fulfillment->order()->first() ?? $fulfillment->order;

        if ($order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            $this->webhooks->dispatch($order->store, 'order.fulfilled', $this->orderPayload($order));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(Order $order): array
    {
        return [
            'id' => $order->getKey(),
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'fulfillment_status' => $order->fulfillment_status->value,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'email' => $order->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fulfillmentPayload(Fulfillment $fulfillment): array
    {
        return [
            'id' => $fulfillment->getKey(),
            'order_id' => $fulfillment->order_id,
            'status' => $fulfillment->status->value,
            'tracking_company' => $fulfillment->tracking_company,
            'tracking_number' => $fulfillment->tracking_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productPayload(Product $product): array
    {
        return [
            'id' => $product->getKey(),
            'title' => $product->title,
            'handle' => $product->handle,
            'status' => $product->status->value,
        ];
    }
}
