<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Models\Store;
use App\Services\WebhookService;

class DispatchOrderRefundedWebhook
{
    public function __construct(
        private WebhookService $webhookService,
    ) {}

    public function handle(OrderRefunded $event): void
    {
        $order = $event->order;
        /** @var Store|null $store */
        $store = $order->store;

        if ($store) {
            $this->webhookService->dispatch($store, 'order.refunded', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_id' => $event->refund->id,
            ]);
        }
    }
}
