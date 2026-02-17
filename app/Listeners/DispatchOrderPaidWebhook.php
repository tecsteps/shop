<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchOrderPaidWebhook
{
    public function __construct(
        private WebhookService $webhookService,
    ) {}

    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        /** @var Store|null $store */
        $store = $order->store;

        if ($store) {
            $this->webhookService->dispatch($store, 'order.paid', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
