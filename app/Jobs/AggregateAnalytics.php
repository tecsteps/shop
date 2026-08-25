<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $date = now()->subDay()->toDateString();

        $storeIds = AnalyticsEvent::distinct()->pluck('store_id');

        foreach ($storeIds as $storeId) {
            $events = AnalyticsEvent::where('store_id', $storeId)
                ->whereDate('created_at', $date)
                ->get();

            $ordersCount = $events->where('type', 'checkout_completed')->count();
            $revenue = $events->where('type', 'checkout_completed')->sum(fn ($e) => (int) ($e->properties_json['total'] ?? 0));
            $visits = $events->where('type', 'page_view')->unique('session_id')->count();

            AnalyticsDaily::updateOrCreate(
                ['store_id' => $storeId, 'date' => $date],
                [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $ordersCount > 0 ? intdiv($revenue, $ordersCount) : 0,
                    'visits_count' => $visits,
                    'add_to_cart_count' => $events->where('type', 'add_to_cart')->count(),
                    'checkout_started_count' => $events->where('type', 'checkout_started')->count(),
                    'checkout_completed_count' => $ordersCount,
                ]
            );
        }
    }
}
