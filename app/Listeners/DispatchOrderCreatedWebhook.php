<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Store;
use App\Services\WebhookService;

/**
 * Fans out the `order.created` outbound webhook to every active subscription on
 * the order's store when an order is created.
 */
class DispatchOrderCreatedWebhook
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        $store = Store::query()->find($order->store_id);

        if ($store === null) {
            return;
        }

        $this->webhooks->dispatch($store, 'order.created', [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'email' => $order->email,
        ]);
    }
}
