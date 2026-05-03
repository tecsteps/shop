<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchWebhooks
{
    public function handle(object $event): void
    {
        $eventType = $this->eventType($event);
        $store = $this->store($event);

        if ($eventType === null || ! $store instanceof Store) {
            return;
        }

        app(WebhookService::class)->dispatch($store, $eventType, $this->payload($event));
    }

    private function eventType(object $event): ?string
    {
        return match ($event::class) {
            OrderCreated::class => 'order.created',
            OrderPaid::class => 'order.paid',
            OrderFulfilled::class => 'order.fulfilled',
            OrderRefunded::class => 'order.refunded',
            ProductCreated::class => 'product.created',
            ProductUpdated::class => 'product.updated',
            ProductDeleted::class => 'product.deleted',
            default => null,
        };
    }

    private function store(object $event): ?Store
    {
        if (property_exists($event, 'order') && $event->order instanceof Order) {
            return Store::query()->find($event->order->store_id);
        }

        if (property_exists($event, 'product') && $event->product instanceof Product) {
            return Store::query()->find($event->product->store_id);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(object $event): array
    {
        if (property_exists($event, 'order') && $event->order instanceof Order) {
            return [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'status' => $event->order->status->value,
                'financial_status' => $event->order->financial_status->value,
                'fulfillment_status' => $event->order->fulfillment_status->value,
                'total_amount' => $event->order->total_amount,
                'currency' => $event->order->currency,
            ];
        }

        if (property_exists($event, 'product') && $event->product instanceof Product) {
            return [
                'product_id' => $event->product->id,
                'title' => $event->product->title,
                'handle' => $event->product->handle,
                'status' => $event->product->status->value,
            ];
        }

        return [];
    }
}
