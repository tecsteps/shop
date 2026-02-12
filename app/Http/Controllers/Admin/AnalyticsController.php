<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends AdminController
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        $baseOrdersQuery = Order::query()
            ->whereNotNull('placed_at')
            ->whereBetween('placed_at', [$startDate, $endDate]);

        $totalSales = (int) (clone $baseOrdersQuery)->sum('total_amount');
        $ordersCount = (int) (clone $baseOrdersQuery)->count();
        $averageOrderValue = $ordersCount > 0
            ? (int) round($totalSales / $ordersCount)
            : 0;

        $unitsSold = (int) DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $this->currentStore()->id)
            ->whereNotNull('orders.placed_at')
            ->whereBetween('orders.placed_at', [$startDate, $endDate])
            ->sum('order_lines.quantity');

        $topProducts = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_lines.product_id')
            ->where('orders.store_id', $this->currentStore()->id)
            ->whereNotNull('orders.placed_at')
            ->whereBetween('orders.placed_at', [$startDate, $endDate])
            ->selectRaw('order_lines.product_id, COALESCE(products.title, order_lines.title_snapshot) as title, SUM(order_lines.quantity) as units_sold, SUM(order_lines.total_amount) as revenue_amount')
            ->groupBy('order_lines.product_id', 'products.title', 'order_lines.title_snapshot')
            ->orderByDesc('revenue_amount')
            ->limit(10)
            ->get();

        return view('admin.analytics.index', [
            'currency' => $this->currentStore()->default_currency,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $averageOrderValue,
            'unitsSold' => $unitsSold,
            'topProducts' => $topProducts,
        ]);
    }
}
