<?php

namespace App\Services;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function track(
        Store $store,
        string|AnalyticsEventType $type,
        array $properties = [],
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $clientEventId = null,
        ?Carbon $occurredAt = null,
    ): AnalyticsEvent {
        $typeValue = $type instanceof AnalyticsEventType ? $type->value : $type;

        if ($clientEventId !== null) {
            $existing = AnalyticsEvent::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('client_event_id', $clientEventId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return AnalyticsEvent::query()->create([
            'store_id' => $store->id,
            'type' => $typeValue,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => $properties,
            'client_event_id' => $clientEventId,
            'occurred_at' => $occurredAt ?? now(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, AnalyticsDaily>
     */
    public function getDailyMetrics(Store $store, string $startDate, string $endDate): Collection
    {
        return AnalyticsDaily::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    public function aggregate(Store $store, string $date): AnalyticsDaily
    {
        $start = Carbon::parse($date.' 00:00:00');
        $end = Carbon::parse($date.' 23:59:59.999999');

        $eventCounts = DB::table('analytics_events')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('type, COUNT(*) as total, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $visitsCount = DB::table('analytics_events')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->count('session_id');

        $addToCartCount = (int) ($eventCounts[AnalyticsEventType::AddToCart->value]->total ?? 0);
        $checkoutStartedCount = (int) ($eventCounts[AnalyticsEventType::CheckoutStarted->value]->total ?? 0);
        $checkoutCompletedCount = (int) ($eventCounts[AnalyticsEventType::CheckoutCompleted->value]->total ?? 0);

        $orderStats = DB::table('orders')
            ->where('store_id', $store->id)
            ->whereBetween('placed_at', [$start, $end])
            ->where('financial_status', 'paid')
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        $ordersCount = (int) ($orderStats->orders_count ?? 0);
        $revenueAmount = (int) ($orderStats->revenue ?? 0);
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

        return AnalyticsDaily::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('date', $date)
            ->first();
    }
}
