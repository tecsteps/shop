<?php

namespace App\Listeners;

use App\Enums\AnalyticsEventType;
use App\Events\OrderCreated;
use App\Models\Store;
use App\Services\AnalyticsService;

/**
 * Records a server-side `checkout_completed` analytics event when an order is
 * created, so order conversions are captured even without a client-side beacon.
 */
class RecordOrderAnalytics
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        $store = Store::query()->find($order->store_id);

        if ($store === null) {
            return;
        }

        $this->analytics->track(
            store: $store,
            type: AnalyticsEventType::CheckoutCompleted->value,
            properties: [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
            customerId: $order->customer_id,
        );
    }
}
