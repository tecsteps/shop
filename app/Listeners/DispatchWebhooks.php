<?php

namespace App\Listeners;

use App\Enums\ProductStatus;
use App\Enums\WebhookEventType;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductStatusChanged;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchWebhooks
{
    public function __construct(private WebhookService $webhooks) {}

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        match (true) {
            $event instanceof OrderCreated => $this->dispatchOrder($event->order, WebhookEventType::OrderCreated),
            $event instanceof OrderPaid => $this->dispatchOrder($event->order, WebhookEventType::OrderPaid),
            $event instanceof OrderCancelled => $this->dispatchOrder($event->order, WebhookEventType::OrderCancelled),
            $event instanceof OrderRefunded => $this->dispatchRefund($event),
            $event instanceof FulfillmentCreated => $this->dispatchFulfillment($event->fulfillment, WebhookEventType::FulfillmentCreated),
            $event instanceof FulfillmentShipped,
            $event instanceof FulfillmentDelivered => $this->dispatchFulfillment($event->fulfillment, WebhookEventType::OrderFulfilled),
            $event instanceof ProductStatusChanged => $this->dispatchProductStatusChange($event),
            default => null,
        };
    }

    private function dispatchRefund(OrderRefunded $event): void
    {
        $payload = $this->orderPayload($event->order);
        $payload['refund'] = [
            'id' => $event->refund->getKey(),
            'amount' => $event->refund->amount,
            'reason' => $event->refund->reason,
            'status' => $event->refund->status?->value,
        ];

        $store = $this->store($event->order->store_id);

        $this->webhooks->dispatch($store, WebhookEventType::OrderRefunded->value, $payload);
        $this->webhooks->dispatch($store, WebhookEventType::RefundCreated->value, $payload);
    }

    private function dispatchOrder(Order $order, WebhookEventType $eventType): void
    {
        $this->webhooks->dispatch($this->store($order->store_id), $eventType->value, $this->orderPayload($order));
    }

    private function dispatchFulfillment(Fulfillment $fulfillment, WebhookEventType $eventType): void
    {
        $order = $fulfillment->order()->withoutGlobalScopes()->firstOrFail();

        $payload = $this->orderPayload($order);
        $payload['fulfillment'] = [
            'id' => $fulfillment->getKey(),
            'status' => $fulfillment->status?->value,
            'tracking_company' => $fulfillment->tracking_company,
            'tracking_number' => $fulfillment->tracking_number,
            'tracking_url' => $fulfillment->tracking_url,
        ];

        $this->webhooks->dispatch($this->store($order->store_id), $eventType->value, $payload);
    }

    private function dispatchProductStatusChange(ProductStatusChanged $event): void
    {
        $eventType = $event->to === ProductStatus::Archived
            ? WebhookEventType::ProductDeleted
            : WebhookEventType::ProductUpdated;

        $this->webhooks->dispatch($this->store($event->product->store_id), $eventType->value, $this->productPayload($event->product));
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'order' => [
                'id' => $order->getKey(),
                'order_number' => $order->order_number,
                'status' => $order->status?->value,
                'financial_status' => $order->financial_status?->value,
                'fulfillment_status' => $order->fulfillment_status?->value,
                'currency' => $order->currency,
                'total_amount' => $order->total_amount,
                'placed_at' => $order->placed_at?->toISOString(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return [
            'product' => [
                'id' => $product->getKey(),
                'title' => $product->title,
                'handle' => $product->handle,
                'status' => $product->status?->value,
                'updated_at' => $product->updated_at?->toISOString(),
            ],
        ];
    }

    private function store(int $storeId): Store
    {
        return Store::query()->findOrFail($storeId);
    }
}
