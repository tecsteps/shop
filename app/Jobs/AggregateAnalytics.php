<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ?string $dateToAggregate = null,
    ) {}

    public function handle(): void
    {
        $date = $this->dateToAggregate
            ? Carbon::parse($this->dateToAggregate)
            : Carbon::yesterday();

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $stores = Store::withoutGlobalScopes()->get();

        foreach ($stores as $store) {
            $events = AnalyticsEvent::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->get();

            $ordersCount = $events->where('event_type', 'checkout_completed')->count();

            $revenueAmount = $events->where('event_type', 'checkout_completed')
                ->sum(fn ($event) => $event->properties_json['total_amount'] ?? 0);

            $aovAmount = $ordersCount > 0 ? (int) round($revenueAmount / $ordersCount) : 0;

            $visitsCount = $events->where('event_type', 'page_view')
                ->whereNotNull('session_id')
                ->unique('session_id')
                ->count();

            $addToCartCount = $events->where('event_type', 'add_to_cart')->count();
            $checkoutStartedCount = $events->where('event_type', 'checkout_started')->count();
            $checkoutCompletedCount = $ordersCount;

            $existing = AnalyticsDaily::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereDate('date', $date->toDateString())
                ->first();

            if ($existing) {
                $existing->update([
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenueAmount,
                    'aov_amount' => $aovAmount,
                    'visits_count' => $visitsCount,
                    'add_to_cart_count' => $addToCartCount,
                    'checkout_started_count' => $checkoutStartedCount,
                    'checkout_completed_count' => $checkoutCompletedCount,
                ]);
            } else {
                AnalyticsDaily::withoutGlobalScopes()->create([
                    'store_id' => $store->id,
                    'date' => $date->toDateString(),
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenueAmount,
                    'aov_amount' => $aovAmount,
                    'visits_count' => $visitsCount,
                    'add_to_cart_count' => $addToCartCount,
                    'checkout_started_count' => $checkoutStartedCount,
                    'checkout_completed_count' => $checkoutCompletedCount,
                ]);
            }
        }
    }
}
