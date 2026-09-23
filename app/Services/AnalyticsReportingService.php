<?php

namespace App\Services;

use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AnalyticsReportingService
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     currency: string,
     *     total_sales: int,
     *     orders_count: int,
     *     average_order_value: int,
     *     conversion_rate: float,
     *     visits_count: int,
     *     add_to_cart_count: int,
     *     checkout_started_count: int,
     *     daily: list<array{date: string, revenue_amount: int, orders_count: int, revenue_percent: float}>,
     *     top_products: list<array{product_id: ?int, title: string, units_sold: int, revenue_amount: int, revenue_percent: float}>,
     *     top_referrers: list<array{source: string, sessions: int, orders: int, conversion_rate: float}>
     * }
     */
    public function report(
        Store $store,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $channel = 'all',
        string $device = 'all',
    ): array {
        if ($to->lt($from) || $from->diffInDays($to) > 364) {
            throw new InvalidArgumentException('Analytics date range must be ordered and no longer than 365 days.');
        }

        if (! in_array($channel, ['all', 'storefront', 'api'], true)) {
            throw new InvalidArgumentException('Unsupported analytics channel.');
        }

        if (! in_array($device, ['all', 'desktop', 'mobile', 'tablet'], true)) {
            throw new InvalidArgumentException('Unsupported analytics device.');
        }

        $fromDate = $from->startOfDay();
        $toDate = $to->endOfDay();
        $orderQuery = DB::table('orders')
            ->where('orders.store_id', $store->getKey())
            ->whereBetween('orders.placed_at', [$fromDate->toDateTimeString(), $toDate->toDateTimeString()])
            ->whereIn('orders.financial_status', ['paid', 'partially_refunded']);
        $eventOrderIds = $this->checkoutOrderIds($store, $fromDate, $toDate, $channel, $device);

        if ($eventOrderIds !== null) {
            $orderQuery->whereIn('orders.id', $eventOrderIds);
        }

        $dailyOrders = (clone $orderQuery)
            ->selectRaw('DATE(orders.placed_at) AS report_date')
            ->selectRaw('COUNT(*) AS orders_count')
            ->selectRaw(
                'COALESCE(SUM(MAX(0, orders.total_amount - COALESCE((SELECT SUM(refunds.amount) FROM refunds WHERE refunds.order_id = orders.id AND refunds.status = ?), 0))), 0) AS revenue_amount',
                ['processed'],
            )
            ->groupByRaw('DATE(orders.placed_at)')
            ->orderBy('report_date')
            ->get()
            ->keyBy('report_date');

        $totalSales = (int) $dailyOrders->sum('revenue_amount');
        $ordersCount = (int) $dailyOrders->sum('orders_count');
        $daily = [];

        for ($date = $fromDate; $date->lte($toDate); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $row = $dailyOrders->get($dateString);
            $revenueAmount = (int) ($row->revenue_amount ?? 0);

            $daily[] = [
                'date' => $dateString,
                'revenue_amount' => $revenueAmount,
                'orders_count' => (int) ($row->orders_count ?? 0),
                'revenue_percent' => $totalSales > 0 ? round(($revenueAmount / $totalSales) * 100, 2) : 0.0,
            ];
        }

        $eventMetrics = $this->eventsQuery($store, $fromDate, $toDate, $channel, $device)
            ->selectRaw('COUNT(DISTINCT CASE WHEN type = ? THEN session_id END) AS visits_count', ['page_view'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS add_to_cart_count', ['add_to_cart'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS checkout_started_count', ['checkout_started'])
            ->first();
        $visitsCount = (int) ($eventMetrics->visits_count ?? 0);
        $topProductsQuery = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $store->getKey())
            ->whereBetween('orders.placed_at', [$fromDate->toDateTimeString(), $toDate->toDateTimeString()])
            ->whereIn('orders.financial_status', ['paid', 'partially_refunded']);

        if ($eventOrderIds !== null) {
            $topProductsQuery->whereIn('orders.id', $eventOrderIds);
        }

        $topProducts = $topProductsQuery
            ->select(['order_lines.product_id', 'order_lines.title_snapshot as title'])
            ->selectRaw('SUM(order_lines.quantity) AS units_sold')
            ->selectRaw('SUM(order_lines.total_amount) AS revenue_amount')
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc('revenue_amount')
            ->orderBy('title')
            ->limit(20)
            ->get()
            ->map(static fn (object $row): array => [
                'product_id' => $row->product_id === null ? null : (int) $row->product_id,
                'title' => (string) $row->title,
                'units_sold' => (int) $row->units_sold,
                'revenue_amount' => (int) $row->revenue_amount,
                'revenue_percent' => $totalSales > 0 ? round(((int) $row->revenue_amount / $totalSales) * 100, 2) : 0.0,
            ])
            ->all();

        $referrerSessions = $this->eventsQuery($store, $fromDate, $toDate, $channel, $device)
            ->where('type', 'page_view')
            ->whereNotNull('session_id')
            ->select('session_id')
            ->selectRaw("COALESCE(NULLIF(json_extract(properties_json, '$.referrer'), ''), 'Direct') AS source")
            ->groupBy('session_id', 'source');
        $completedOrders = $this->eventsQuery($store, $fromDate, $toDate, $channel, $device)
            ->where('type', 'checkout_completed')
            ->whereNotNull('session_id')
            ->whereNotNull(DB::raw("json_extract(properties_json, '$.order_id')"))
            ->select('session_id')
            ->selectRaw("CAST(json_extract(properties_json, '$.order_id') AS TEXT) AS order_id")
            ->groupBy('session_id', 'order_id');
        $topReferrers = DB::query()
            ->fromSub($referrerSessions, 'referrer_sessions')
            ->leftJoinSub($completedOrders, 'completed_orders', 'completed_orders.session_id', '=', 'referrer_sessions.session_id')
            ->select('referrer_sessions.source')
            ->selectRaw('COUNT(DISTINCT referrer_sessions.session_id) AS sessions')
            ->selectRaw('COUNT(DISTINCT completed_orders.order_id) AS orders')
            ->groupBy('referrer_sessions.source')
            ->orderByDesc('sessions')
            ->orderBy('referrer_sessions.source')
            ->limit(10)
            ->get()
            ->map(static fn (object $row): array => [
                'source' => (string) $row->source,
                'sessions' => (int) $row->sessions,
                'orders' => (int) $row->orders,
                'conversion_rate' => (int) $row->sessions > 0 ? round(((int) $row->orders / (int) $row->sessions) * 100, 2) : 0.0,
            ])
            ->all();

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'currency' => $store->default_currency,
            'total_sales' => $totalSales,
            'orders_count' => $ordersCount,
            'average_order_value' => $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0,
            'conversion_rate' => $visitsCount > 0 ? round(($ordersCount / $visitsCount) * 100, 2) : 0.0,
            'visits_count' => $visitsCount,
            'add_to_cart_count' => (int) ($eventMetrics->add_to_cart_count ?? 0),
            'checkout_started_count' => (int) ($eventMetrics->checkout_started_count ?? 0),
            'daily' => $daily,
            'top_products' => $topProducts,
            'top_referrers' => $topReferrers,
        ];
    }

    private function checkoutOrderIds(Store $store, CarbonImmutable $from, CarbonImmutable $to, string $channel, string $device): ?Builder
    {
        if ($channel === 'all' && $device === 'all') {
            return null;
        }

        return $this->eventsQuery($store, $from, $to, $channel, $device)
            ->where('type', 'checkout_completed')
            ->whereNotNull(DB::raw("json_extract(properties_json, '$.order_id')"))
            ->selectRaw("CAST(json_extract(properties_json, '$.order_id') AS INTEGER)");
    }

    private function eventsQuery(Store $store, CarbonImmutable $from, CarbonImmutable $to, string $channel, string $device): Builder
    {
        return DB::table('analytics_events')
            ->where('store_id', $store->getKey())
            ->whereBetween(DB::raw('COALESCE(occurred_at, created_at)'), [$from->toDateTimeString(), $to->toDateTimeString()])
            ->when($channel !== 'all', fn (Builder $query): Builder => $query->whereRaw("json_extract(properties_json, '$.channel') = ?", [$channel]))
            ->when($device !== 'all', fn (Builder $query): Builder => $query->whereRaw("json_extract(properties_json, '$.device') = ?", [$device]));
    }
}
