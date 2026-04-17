<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $date = null
    ) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->format('Y-m-d');

        $stores = Store::all();

        foreach ($stores as $store) {
            $this->aggregateForStore($store, $date);
        }
    }

    protected function aggregateForStore(Store $store, string $date): void
    {
        $events = AnalyticsEvent::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereDate('created_at', $date);

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

        $orderData = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereDate('placed_at', $date)
            ->whereNotIn('status', ['cancelled'])
            ->select(
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('COALESCE(SUM(total_amount), 0) as revenue_amount')
            )
            ->first();

        /** @var int $ordersCount */
        $ordersCount = $orderData->orders_count ?? 0;
        /** @var int $revenueAmount */
        $revenueAmount = $orderData->revenue_amount ?? 0;
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
            [
                'orders_count',
                'revenue_amount',
                'aov_amount',
                'visits_count',
                'add_to_cart_count',
                'checkout_started_count',
                'checkout_completed_count',
            ]
        );
    }
}
