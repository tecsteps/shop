<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Support\Storefront\PriceFormatter;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::admin')]
class Dashboard extends Component
{
    /**
     * Date range preset in days: 7, 30, or 90 (spec 03 section 2).
     */
    #[Url]
    public string $dateRange = '30';

    public function render(): View
    {
        $days = $this->rangeDays();
        $end = now();
        $start = $end->startOfDay()->subDays($days - 1);
        $previousStart = $start->subDays($days);

        $current = $this->kpisBetween($start, $end);
        $previous = $this->kpisBetween($previousStart, $start);

        return view('livewire.admin.dashboard', [
            'totalSales' => $current['total_sales'],
            'ordersCount' => $current['orders_count'],
            'averageOrderValue' => $current['average_order_value'],
            'conversionRate' => $current['conversion_rate'],
            'salesChange' => $this->percentChange($previous['total_sales'], $current['total_sales']),
            'ordersChange' => $this->percentChange($previous['orders_count'], $current['orders_count']),
            'aovChange' => $this->percentChange($previous['average_order_value'], $current['average_order_value']),
            'conversionChange' => $this->percentChange($previous['conversion_rate'], $current['conversion_rate']),
            'formattedTotalSales' => PriceFormatter::format($current['total_sales'], $this->currency()),
            'formattedAov' => PriceFormatter::format($current['average_order_value'], $this->currency()),
            'chart' => $this->ordersChart($start, $end),
            'recentOrders' => $this->recentOrders(),
        ])->title(__('Dashboard'));
    }

    /**
     * Aggregate order KPIs for the half-open interval [start, end).
     * Cancelled orders are excluded from revenue figures.
     *
     * The conversion rate is approximated as orders placed / carts created
     * until session-based analytics land in Phase 9 (integration point:
     * replace the cart count with tracked storefront visits).
     *
     * @return array{total_sales: int, orders_count: int, average_order_value: int, conversion_rate: float}
     */
    protected function kpisBetween(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $orders = Order::query()
            ->where('placed_at', '>=', $start)
            ->where('placed_at', '<', $end)
            ->where('status', '!=', OrderStatus::Cancelled);

        $ordersCount = (clone $orders)->count();
        $totalSales = (int) (clone $orders)->sum('total_amount');

        $cartsCount = Cart::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();

        return [
            'total_sales' => $totalSales,
            'orders_count' => $ordersCount,
            'average_order_value' => $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0,
            'conversion_rate' => $cartsCount > 0 ? round($ordersCount / $cartsCount * 100, 1) : 0.0,
        ];
    }

    /**
     * Daily order counts plus the precomputed SVG polyline geometry for the
     * dependency-free inline line chart.
     *
     * @return array{days: list<array{date: string, count: int}>, max: int, points: string, area: string}
     */
    protected function ordersChart(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $countsByDay = Order::query()
            ->where('placed_at', '>=', $start)
            ->where('placed_at', '<', $end)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->selectRaw('date(placed_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];

        for ($date = $start; $date < $end; $date = $date->addDay()) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'count' => (int) ($countsByDay[$date->format('Y-m-d')] ?? 0),
            ];
        }

        $max = max(1, ...array_column($days, 'count'));

        $width = 600;
        $height = 180;
        $stepX = count($days) > 1 ? $width / (count($days) - 1) : $width;

        $points = [];

        foreach ($days as $index => $day) {
            $x = round($index * $stepX, 1);
            $y = round($height - ($day['count'] / $max) * ($height - 10) - 5, 1);
            $points[] = "{$x},{$y}";
        }

        $polyline = implode(' ', $points);
        $area = "0,{$height} ".$polyline." {$width},{$height}";

        return [
            'days' => $days,
            'max' => $max,
            'points' => $polyline,
            'area' => $area,
        ];
    }

    /**
     * The ten most recently placed orders for the recent orders table.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    protected function recentOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::query()
            ->with('customer')
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get();
    }

    protected function rangeDays(): int
    {
        return in_array($this->dateRange, ['7', '30', '90'], true) ? (int) $this->dateRange : 30;
    }

    protected function currency(): string
    {
        return app('current_store')->default_currency ?? 'EUR';
    }

    protected function percentChange(int|float $previous, int|float $current): float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
