<?php

namespace App\Listeners;

use App\Events\OrderFulfilled;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchOrderFulfilledWebhook
{
    public function __construct(
        private WebhookService $webhookService,
    ) {}

    public function handle(OrderFulfilled $event): void
    {
        $order = $event->order;
        /** @var Store|null $store */
        $store = $order->store;

        if ($store) {
            $this->webhookService->dispatch($store, 'order.fulfilled', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
