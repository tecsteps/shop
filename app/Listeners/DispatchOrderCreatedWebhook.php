<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Store;
use App\Services\WebhookService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fans out the `order.created` outbound webhook to every active subscription on
 * the order's store when an order is created.
 *
 * Runs AFTER the surrounding DB transaction commits ({@see ShouldHandleEventsAfterCommit})
 * so the order is durably persisted before any delivery work — and any error in
 * fan-out is swallowed (logged) so webhook problems can never break checkout.
 */
class DispatchOrderCreatedWebhook implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        try {
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
        } catch (Throwable $exception) {
            // Webhook fan-out must never break order creation; log and move on.
            Log::warning('order.created webhook dispatch failed', [
                'order_id' => $order->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
