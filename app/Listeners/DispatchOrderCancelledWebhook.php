<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchOrderCancelledWebhook
{
    public function __construct(
        private WebhookService $webhookService,
    ) {}

    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;
        /** @var Store|null $store */
        $store = $order->store;

        if ($store) {
            $this->webhookService->dispatch($store, 'order.cancelled', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
