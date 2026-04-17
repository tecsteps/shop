<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
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
        private ?string $date = null,
    ) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->toDateString();

        $stores = Store::query()->get();

        foreach ($stores as $store) {
            $this->aggregateForStore($store, $date);
        }
    }

    private function aggregateForStore(Store $store, string $date): void
    {
        $startOfDay = $date.' 00:00:00';
        $endOfDay = $date.' 23:59:59';

        $eventCounts = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type');

        /** @var int $ordersCount */
        $ordersCount = $eventCounts->get('checkout_completed', 0);

        $revenue = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$startOfDay, $endOfDay])
            ->sum('total_amount');

        $aov = $ordersCount > 0 ? (int) round($revenue / $ordersCount) : 0;

        /** @var int $visits */
        $visits = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', 'page_view')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereNotNull('session_id')
            ->selectRaw('count(distinct session_id) as cnt')
            ->value('cnt') ?? 0;

        /** @var int $addToCartCount */
        $addToCartCount = $eventCounts->get('add_to_cart', 0);
        /** @var int $checkoutStartedCount */
        $checkoutStartedCount = $eventCounts->get('checkout_started', 0);

        AnalyticsDaily::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $store->id,
                'date' => $date,
            ],
            [
                'orders_count' => $ordersCount,
                'revenue_amount' => (int) $revenue,
                'aov_amount' => $aov,
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCartCount,
                'checkout_started_count' => $checkoutStartedCount,
            ],
        );
    }
}
