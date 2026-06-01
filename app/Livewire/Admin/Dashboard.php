<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Order;
use App\Support\Storefront\PriceFormatter;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin dashboard: revenue/order KPIs with period-over-period comparison, a
 * 30-day orders chart, and a recent-orders table. All figures are derived from
 * the orders table scoped to the current store, filtered by the selected date
 * range. Sales count placed (non-cancelled) orders only.
 */
#[Layout('livewire.admin.layout.app')]
class Dashboard extends Component
{
    use BindsCurrentStore;

    #[Url]
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Order::class);
    }

    /**
     * The [start, end] Carbon bounds of the active period.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodBounds(): array
    {
        $end = Carbon::now()->endOfDay();

        return match ($this->dateRange) {
            'today' => [Carbon::now()->startOfDay(), $end],
            'last_7_days' => [Carbon::now()->subDays(6)->startOfDay(), $end],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay(),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : $end,
            ],
            default => [Carbon::now()->subDays(29)->startOfDay(), $end],
        };
    }

    /**
     * Base query: placed (non-cancelled) orders within a time window.
     */
    private function placedOrdersBetween(Carbon $start, Carbon $end)
    {
        return Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('placed_at', [$start, $end]);
    }

    /**
     * Aggregated KPI figures for the current period plus deltas vs. the
     * immediately preceding period of equal length.
     *
     * @return array<string, int|float>
     */
    public function getKpisProperty(): array
    {
        [$start, $end] = $this->periodBounds();

        $lengthSeconds = $end->getTimestamp() - $start->getTimestamp();
        $prevEnd = (clone $start)->subSecond();
        $prevStart = (clone $prevEnd)->subSeconds($lengthSeconds);

        $totalSales = (int) $this->placedOrdersBetween($start, $end)->sum('total_amount');
        $ordersCount = $this->placedOrdersBetween($start, $end)->count();
        $aov = $ordersCount > 0 ? intdiv($totalSales, $ordersCount) : 0;

        $prevSales = (int) $this->placedOrdersBetween($prevStart, $prevEnd)->sum('total_amount');
        $prevOrders = $this->placedOrdersBetween($prevStart, $prevEnd)->count();
        $prevAov = $prevOrders > 0 ? intdiv($prevSales, $prevOrders) : 0;

        return [
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $aov,
            'conversionRate' => 0.0,
            'salesChange' => $this->percentChange($prevSales, $totalSales),
            'ordersChange' => $this->percentChange($prevOrders, $ordersCount),
            'aovChange' => $this->percentChange($prevAov, $aov),
            'conversionChange' => 0.0,
        ];
    }

    /**
     * Daily order counts for the last 30 days, for the orders chart.
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function getChartDataProperty(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $counts = $this->placedOrdersBetween($start, Carbon::now()->endOfDay())
            ->selectRaw('date(placed_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];

        for ($i = 0; $i < 30; $i++) {
            $day = (clone $start)->addDays($i)->toDateString();
            $series[] = ['date' => $day, 'count' => (int) ($counts[$day] ?? 0)];
        }

        return $series;
    }

    /**
     * The ten most recently placed orders (any status) for the activity table.
     *
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function getRecentOrdersProperty()
    {
        return Order::query()
            ->with('customer')
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get();
    }

    public function getFormattedTotalSalesProperty(): string
    {
        return PriceFormatter::format($this->kpis['totalSales'], $this->currency());
    }

    public function getFormattedAovProperty(): string
    {
        return PriceFormatter::format($this->kpis['averageOrderValue'], $this->currency());
    }

    private function currency(): string
    {
        return app()->bound('current_store')
            ? app('current_store')->default_currency
            : 'USD';
    }

    private function percentChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
