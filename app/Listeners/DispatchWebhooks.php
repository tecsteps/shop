<?php

namespace App\Listeners;

use App\Services\WebhookService;

class DispatchWebhooks
{
    public function __construct(private readonly WebhookService $webhookService) {}

    public function handle(object $event): void
    {
        $map = [
            \App\Events\OrderCreated::class => ['order.created'],
            \App\Events\OrderPaid::class => ['order.paid'],
            \App\Events\OrderFulfilled::class => ['order.fulfilled'],
            \App\Events\OrderRefunded::class => ['order.refunded'],
            \App\Events\CheckoutCompleted::class => ['checkout.completed'],
            \App\Events\ProductCreated::class => ['product.created'],
            \App\Events\ProductUpdated::class => ['product.updated'],
            \App\Events\ProductDeleted::class => ['product.deleted'],
        ];

        $types = $map[get_class($event)] ?? [];

        foreach ($types as $type) {
            $store = $event->model->store ?? $event->model->store();

            if ($store && isset($store->id)) {
                $this->webhookService->dispatch($store, $type, ['id' => $event->model->id]);
            }
        }
    }
}
