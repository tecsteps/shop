<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $date = null) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->format('Y-m-d');

        $startOfDay = $date.'T00:00:00';
        $endOfDay = $date.'T23:59:59';

        $storeIds = AnalyticsEvent::withoutGlobalScopes()
            ->where('created_at', '>=', $startOfDay)
            ->where('created_at', '<=', $endOfDay)
            ->distinct()
            ->pluck('store_id');

        foreach ($storeIds as $storeId) {
            $this->aggregateForStore($storeId, $date, $startOfDay, $endOfDay);
        }
    }

    protected function aggregateForStore(int $storeId, string $date, string $startOfDay, string $endOfDay): void
    {
        $baseQuery = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('created_at', '>=', $startOfDay)
            ->where('created_at', '<=', $endOfDay);

        $visitsCount = (clone $baseQuery)
            ->where('type', 'page_view')
            ->distinct('session_id')
            ->count('session_id');

        $addToCartCount = (clone $baseQuery)
            ->where('type', 'add_to_cart')
            ->count();

        $checkoutStartedCount = (clone $baseQuery)
            ->where('type', 'checkout_started')
            ->count();

        $checkoutCompletedEvents = (clone $baseQuery)
            ->where('type', 'checkout_completed')
            ->get();

        $ordersCount = $checkoutCompletedEvents->count();

        $revenueAmount = $checkoutCompletedEvents->sum(function ($event) {
            $properties = is_array($event->properties_json)
                ? $event->properties_json
                : json_decode($event->properties_json, true);

            return $properties['order_total'] ?? 0;
        });

        $aovAmount = $ordersCount > 0 ? (int) round($revenueAmount / $ordersCount) : 0;

        AnalyticsDaily::withoutGlobalScopes()->upsert(
            [
                'store_id' => $storeId,
                'date' => $date,
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $aovAmount,
                'visits_count' => $visitsCount,
                'add_to_cart_count' => $addToCartCount,
                'checkout_started_count' => $checkoutStartedCount,
                'checkout_completed_count' => $ordersCount,
            ],
            ['store_id', 'date'],
            ['orders_count', 'revenue_amount', 'aov_amount', 'visits_count', 'add_to_cart_count', 'checkout_started_count', 'checkout_completed_count'],
        );
    }
}
