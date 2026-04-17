<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $date = null) {}

    public function handle(): void
    {
        $date = $this->date ?? Carbon::yesterday()->format('Y-m-d');

        $stores = Store::all();
        $aggregated = 0;

        foreach ($stores as $store) {
            $this->aggregateForStore($store, $date);
            $aggregated++;
        }

        Log::info('Aggregated analytics', ['date' => $date, 'stores' => $aggregated]);
    }

    private function aggregateForStore(Store $store, string $date): void
    {
        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();

        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->get();

        $visitsCount = $events->where('type', 'page_view')
            ->pluck('session_id')
            ->filter()
            ->unique()
            ->count();

        $addToCartCount = $events->where('type', 'add_to_cart')->count();
        $checkoutStartedCount = $events->where('type', 'checkout_started')->count();
        $checkoutCompletedCount = $events->where('type', 'checkout_completed')->count();

        $orders = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotNull('placed_at')
            ->whereBetween('placed_at', [$dayStart, $dayEnd])
            ->get();

        $ordersCount = $orders->count();
        $revenueAmount = (int) $orders->sum('total_amount');
        $aovAmount = $ordersCount > 0 ? (int) ($revenueAmount / $ordersCount) : 0;

        AnalyticsDaily::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $store->id,
                'date' => $date,
            ],
            [
                'orders_count' => $ordersCount,
                'revenue_amount' => $revenueAmount,
                'aov_amount' => $aovAmount,
                'visits_count' => $visitsCount,
                'add_to_cart_count' => $addToCartCount,
                'checkout_started_count' => $checkoutStartedCount,
                'checkout_completed_count' => $checkoutCompletedCount,
            ]
        );
    }
}
