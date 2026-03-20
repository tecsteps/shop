<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?string $date = null,
    ) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->format('Y-m-d');

        $startOfDay = $date.' 00:00:00';
        $endOfDay = $date.' 23:59:59';

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
        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('created_at', '>=', $startOfDay)
            ->where('created_at', '<=', $endOfDay)
            ->get();

        $completedEvents = $events->where('type', 'checkout_completed');
        $ordersCount = $completedEvents->count();

        $revenueAmount = 0;
        foreach ($completedEvents as $event) {
            $revenueAmount += $event->properties_json['order_total'] ?? 0;
        }

        $aovAmount = $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0;

        $visitsCount = $events
            ->where('type', 'page_view')
            ->whereNotNull('session_id')
            ->pluck('session_id')
            ->unique()
            ->count();

        $addToCartCount = $events->where('type', 'add_to_cart')->count();
        $checkoutStartedCount = $events->where('type', 'checkout_started')->count();
        $checkoutCompletedCount = $ordersCount;

        AnalyticsDaily::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $storeId, 'date' => $date],
            [
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $aovAmount,
                'visits_count' => $visitsCount,
                'add_to_cart_count' => $addToCartCount,
                'checkout_started_count' => $checkoutStartedCount,
                'checkout_completed_count' => $checkoutCompletedCount,
            ],
        );
    }
}
