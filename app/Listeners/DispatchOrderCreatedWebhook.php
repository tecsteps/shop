<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchOrderCreatedWebhook
{
    public function __construct(
        private WebhookService $webhookService,
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        /** @var Store|null $store */
        $store = $order->store;

        if ($store) {
            $this->webhookService->dispatch($store, 'order.created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
