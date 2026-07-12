<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Notifications\OrderLifecycleNotification;
use App\Services\AnalyticsService;
use App\Services\OutboundDispatcher;
use App\Services\WebhookService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;

final class ShopEventSubscriber
{
    public function __construct(
        private readonly WebhookService $webhooks,
        private readonly AnalyticsService $analytics,
        private readonly OutboundDispatcher $outbound,
    ) {}

    public function orderCreated(OrderCreated $event): void
    {
        $order = $event->order;
        $store = Store::query()->findOrFail($order->store_id);
        $this->notify($order->email, new OrderLifecycleNotification($order, 'confirmed'));
        $this->webhooks->dispatch($store, 'order.created', $this->orderPayload($order));
        $this->analytics->track(
            $store,
            'checkout_completed',
            ['order_id' => $order->id, 'total_amount' => $order->total_amount],
            customerId: $order->customer_id,
            clientEventId: "order:{$order->id}:completed",
        );
    }

    public function orderRefunded(OrderRefunded $event): void
    {
        $store = Store::query()->findOrFail($event->order->store_id);
        if ($event->notifyCustomer) {
            $this->notify($event->order->email, new OrderLifecycleNotification($event->order, 'refunded', $event->refund));
        }
        $this->webhooks->dispatch($store, 'order.refunded', [
            ...$this->orderPayload($event->order),
            'refund_id' => $event->refund->id,
            'amount' => $event->refund->amount,
        ]);
        $this->webhooks->dispatch($store, 'refund.created', [
            'refund_id' => $event->refund->id,
            'order_id' => $event->order->id,
            'amount' => $event->refund->amount,
        ]);
        $this->webhooks->dispatch($store, 'order.updated', $this->orderPayload($event->order));
    }

    public function orderPaid(OrderPaid $event): void
    {
        $store = Store::query()->findOrFail($event->order->store_id);
        $this->webhooks->dispatch($store, 'order.paid', $this->orderPayload($event->order));
        $this->webhooks->dispatch($store, 'order.updated', $this->orderPayload($event->order));
    }

    public function orderFulfilled(OrderFulfilled $event): void
    {
        $store = Store::query()->findOrFail($event->order->store_id);
        $this->webhooks->dispatch($store, 'order.fulfilled', $this->orderPayload($event->order));
        $this->webhooks->dispatch($store, 'order.updated', $this->orderPayload($event->order));
    }

    public function checkoutCompleted(CheckoutCompleted $event): void
    {
        $store = Store::query()->findOrFail($event->checkout->store_id);
        $this->webhooks->dispatch($store, 'checkout.completed', [
            'checkout_id' => $event->checkout->id,
            'order_id' => $event->order->id,
            'total_amount' => $event->order->total_amount,
        ]);
    }

    public function fulfillmentShipped(FulfillmentShipped $event): void
    {
        $order = $event->fulfillment->order()->withoutGlobalScopes()->firstOrFail();
        $store = Store::query()->findOrFail($order->store_id);
        if ($event->notifyCustomer) {
            $this->notify($order->email, new OrderLifecycleNotification($order, 'shipped', $event->fulfillment));
        }
        $this->webhooks->dispatch($store, 'order.updated', $this->orderPayload($order));
    }

    public function fulfillmentCreated(FulfillmentCreated $event): void
    {
        $order = $event->fulfillment->order()->withoutGlobalScopes()->firstOrFail();
        $store = Store::query()->findOrFail($order->store_id);
        $this->webhooks->dispatch($store, 'fulfillment.created', [
            ...$this->orderPayload($order),
            'fulfillment_id' => $event->fulfillment->id,
        ]);
    }

    public function fulfillmentDelivered(FulfillmentDelivered $event): void
    {
        $order = $event->fulfillment->order()->withoutGlobalScopes()->firstOrFail();
        $store = Store::query()->findOrFail($order->store_id);
        $this->webhooks->dispatch($store, 'fulfillment.delivered', [
            ...$this->orderPayload($order),
            'fulfillment_id' => $event->fulfillment->id,
        ]);
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        $store = Store::query()->findOrFail($event->order->store_id);
        $this->notify($event->order->email, new OrderLifecycleNotification($event->order, 'cancelled', $event->reason));
        $this->webhooks->dispatch($store, 'order.cancelled', [
            ...$this->orderPayload($event->order),
            'reason' => $event->reason,
        ]);
        $this->webhooks->dispatch($store, 'order.updated', $this->orderPayload($event->order));
    }

    public function productCreated(ProductCreated $event): void
    {
        $this->productWebhook($event->product->store_id, 'product.created', $event->product->id);
    }

    public function productUpdated(ProductUpdated $event): void
    {
        $this->productWebhook($event->product->store_id, 'product.updated', $event->product->id);
    }

    public function productDeleted(ProductDeleted $event): void
    {
        $this->productWebhook($event->product->store_id, 'product.deleted', $event->product->id);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(OrderCreated::class, [self::class, 'orderCreated']);
        $events->listen(OrderPaid::class, [self::class, 'orderPaid']);
        $events->listen(OrderFulfilled::class, [self::class, 'orderFulfilled']);
        $events->listen(OrderRefunded::class, [self::class, 'orderRefunded']);
        $events->listen(CheckoutCompleted::class, [self::class, 'checkoutCompleted']);
        $events->listen(FulfillmentShipped::class, [self::class, 'fulfillmentShipped']);
        $events->listen(FulfillmentCreated::class, [self::class, 'fulfillmentCreated']);
        $events->listen(FulfillmentDelivered::class, [self::class, 'fulfillmentDelivered']);
        $events->listen(OrderCancelled::class, [self::class, 'orderCancelled']);
        $events->listen(ProductCreated::class, [self::class, 'productCreated']);
        $events->listen(ProductUpdated::class, [self::class, 'productUpdated']);
        $events->listen(ProductDeleted::class, [self::class, 'productDeleted']);
    }

    /** @return array<string, mixed> */
    private function orderPayload(mixed $order): array
    {
        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
        ];
    }

    private function productWebhook(int $storeId, string $event, int $productId): void
    {
        $this->webhooks->dispatch(Store::query()->findOrFail($storeId), $event, ['product_id' => $productId]);
    }

    private function notify(?string $email, OrderLifecycleNotification $notification): void
    {
        $preference = match ($notification->type) {
            'confirmed' => 'order_confirmation',
            'shipped' => 'shipping_confirmation',
            'refunded' => 'refund_confirmation',
            'cancelled' => 'cancellation_confirmation',
            default => null,
        };
        $settings = (array) StoreSettings::withoutGlobalScopes()
            ->where('store_id', $notification->order->store_id)
            ->value('settings_json');
        $enabled = $preference === null || (bool) data_get($settings, 'notifications.'.$preference, true);
        if ($enabled && $email !== null && $email !== '') {
            $this->outbound->afterCommit(
                fn () => Notification::route('mail', $email)->notify($notification),
            );
        }
    }
}
