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

    public function __construct(public ?string $date = null) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->toDateString();

        $stores = Store::all();

        foreach ($stores as $store) {
            $this->aggregateForStore($store, $date);
        }
    }

    private function aggregateForStore(Store $store, string $date): void
    {
        $startOfDay = $date.' 00:00:00';
        $endOfDay = $date.' 23:59:59';

        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        $visitsCount = (clone $events)->where('type', 'page_view')
            ->distinct('session_id')
            ->count('session_id');

        $addToCartCount = (clone $events)->where('type', 'add_to_cart')->count();
        $checkoutStartedCount = (clone $events)->where('type', 'checkout_started')->count();
        $checkoutCompletedCount = (clone $events)->where('type', 'checkout_completed')->count();

        $orders = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$startOfDay, $endOfDay]);

        $ordersCount = (clone $orders)->count();
        $revenueAmount = (int) (clone $orders)->sum('total_amount');
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
            ['orders_count', 'revenue_amount', 'aov_amount', 'visits_count', 'add_to_cart_count', 'checkout_started_count', 'checkout_completed_count']
        );
    }
}
