<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function track(Store $store, string $type, array $properties = [], ?string $sessionId = null, ?int $customerId = null): void
    {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'type' => $type,
            'properties_json' => $properties,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'created_at' => now(),
        ]);
    }

    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        $metrics = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        if ($metrics->isEmpty() || $metrics->sum('revenue_amount') === 0) {
            return $this->buildMetricsFromOrders($store, $startDate, $endDate);
        }

        return $metrics;
    }

    private function buildMetricsFromOrders(Store $store, string $startDate, string $endDate): Collection
    {
        $orders = \App\Models\Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotNull('placed_at')
            ->whereBetween('placed_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->get();

        $events = AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->get();

        $grouped = $orders->groupBy(fn ($o) => $o->placed_at->format('Y-m-d'));
        $eventsByDate = $events->groupBy(fn ($e) => $e->created_at->format('Y-m-d'));

        $results = collect();
        $current = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        while ($current->lte($end)) {
            $date = $current->format('Y-m-d');
            $dayOrders = $grouped->get($date, collect());
            $dayEvents = $eventsByDate->get($date, collect());

            $results->push((object) [
                'date' => $date,
                'orders_count' => $dayOrders->count(),
                'revenue_amount' => $dayOrders->sum('total_amount'),
                'visits_count' => $dayEvents->where('type', 'page_view')->count(),
                'add_to_cart_count' => $dayEvents->where('type', 'add_to_cart')->count(),
                'checkout_started_count' => $dayEvents->where('type', 'checkout_started')->count(),
                'checkout_completed_count' => $dayEvents->where('type', 'checkout_completed')->count(),
            ]);

            $current->addDay();
        }

        return $results;
    }
}
