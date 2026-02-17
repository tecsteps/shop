<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $date = null,
    ) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->toDateString();

        Store::query()->each(function (Store $store) use ($date): void {
            $this->aggregateForStore($store, $date);
        });
    }

    private function aggregateForStore(Store $store, string $date): void
    {
        $events = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereDate('created_at', $date);

        $visitsCount = (clone $events)->where('type', 'page_view')
            ->distinct('session_id')
            ->count('session_id');

        $addToCartCount = (clone $events)->where('type', 'add_to_cart')->count();
        $checkoutStartedCount = (clone $events)->where('type', 'checkout_started')->count();
        $checkoutCompletedCount = (clone $events)->where('type', 'checkout_completed')->count();

        $orderStats = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereDate('placed_at', $date)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        $ordersCount = (int) $orderStats->count;
        $revenueAmount = (int) $orderStats->revenue;
        $aovAmount = $ordersCount > 0 ? (int) round($revenueAmount / $ordersCount) : 0;

        DB::table('analytics_daily')->upsert(
            [
                'store_id' => $store->id,
                'date' => $date,
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $aovAmount,
                'visits_count' => $visitsCount,
                'add_to_cart_count' => $addToCartCount,
                'checkout_started_count' => $checkoutStartedCount,
                'checkout_completed_count' => $checkoutCompletedCount,
            ],
            ['store_id', 'date'],
            ['orders_count', 'revenue_amount', 'aov_amount', 'visits_count', 'add_to_cart_count', 'checkout_started_count', 'checkout_completed_count'],
        );
    }
}
