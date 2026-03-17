<?php

namespace App\Jobs;

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
        $date = $this->date ?? now()->subDay()->format('Y-m-d');

        Store::query()->each(function (Store $store) use ($date) {
            $this->aggregateForStore($store, $date);
        });
    }

    protected function aggregateForStore(Store $store, string $date): void
    {
        $events = DB::table('analytics_events')
            ->where('store_id', $store->id)
            ->whereRaw('date(created_at) = ?', [$date]);

        $visitsCount = (clone $events)
            ->where('type', 'page_view')
            ->distinct('session_id')
            ->count('session_id');

        $addToCartCount = (clone $events)
            ->where('type', 'add_to_cart')
            ->count();

        $checkoutStartedCount = (clone $events)
            ->where('type', 'checkout_started')
            ->count();

        $checkoutCompletedCount = (clone $events)
            ->where('type', 'checkout_completed')
            ->count();

        $orderStats = DB::table('orders')
            ->where('store_id', $store->id)
            ->whereRaw('date(placed_at) = ?', [$date])
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total_amount), 0) as revenue_amount')
            ->first();

        $ordersCount = $orderStats->orders_count ?? 0;
        $revenueAmount = $orderStats->revenue_amount ?? 0;
        $aovAmount = $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0;

        DB::table('analytics_daily')->updateOrInsert(
            ['store_id' => $store->id, 'date' => $date],
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
