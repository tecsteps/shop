<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\AnalyticsService;

/**
 * Records a checkout_completed analytics event whenever an order is
 * created (spec 05 section 17: OrderCreated -> log analytics event). The
 * event feeds the daily revenue/AOV aggregation.
 */
class LogOrderAnalyticsEvent
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        $this->analytics->track(
            $order->store,
            'checkout_completed',
            [
                'order_id' => $order->getKey(),
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
            session()->isStarted() ? session()->getId() : null,
            $order->customer_id,
        );
    }
}
