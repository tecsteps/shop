<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\OrderLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public function updatedDateRange(): void
    {
        if ($this->dateRange !== 'custom') {
            $this->customStartDate = null;
            $this->customEndDate = null;
        }
    }

    #[Computed]
    public function kpis(): array
    {
        [$start, $end] = $this->getDateRange();
        [$prevStart, $prevEnd] = $this->getPreviousDateRange();

        $current = $this->getKpiData($start, $end);
        $previous = $this->getKpiData($prevStart, $prevEnd);

        return [
            'totalSales' => $current['totalSales'],
            'ordersCount' => $current['ordersCount'],
            'averageOrderValue' => $current['ordersCount'] > 0
                ? (int) round($current['totalSales'] / $current['ordersCount'])
                : 0,
            'salesChange' => $this->percentChange($previous['totalSales'], $current['totalSales']),
            'ordersChange' => $this->percentChange($previous['ordersCount'], $current['ordersCount']),
            'aovChange' => $this->percentChange(
                $previous['ordersCount'] > 0 ? $previous['totalSales'] / $previous['ordersCount'] : 0,
                $current['ordersCount'] > 0 ? $current['totalSales'] / $current['ordersCount'] : 0,
            ),
        ];
    }

    #[Computed]
    public function recentOrders(): mixed
    {
        return Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $this->getStoreId())
            ->with('customer')
            ->latest('placed_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function topProducts(): array
    {
        [$start, $end] = $this->getDateRange();

        return OrderLine::query()
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.store_id', $this->getStoreId())
            ->whereBetween('orders.placed_at', [$start, $end])
            ->select(
                'order_lines.title_snapshot as title',
                DB::raw('SUM(order_lines.quantity) as units_sold'),
                DB::raw('SUM(order_lines.total_amount) as revenue'),
            )
            ->groupBy('order_lines.title_snapshot')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function formatCurrency(int $amountInCents): string
    {
        return '$'.number_format($amountInCents / 100, 2);
    }

    public function render(): mixed
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.admin', [
                'breadcrumbs' => [],
            ]);
    }

    protected function getStoreId(): ?int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store?->id;
    }

    /**
     * @return array{string, string}
     */
    protected function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today()->toDateTimeString(), Carbon::now()->toDateTimeString()],
            'last_7_days' => [Carbon::now()->subDays(7)->toDateTimeString(), Carbon::now()->toDateTimeString()],
            'last_30_days' => [Carbon::now()->subDays(30)->toDateTimeString(), Carbon::now()->toDateTimeString()],
            'custom' => [
                $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay()->toDateTimeString() : Carbon::now()->subDays(30)->toDateTimeString(),
                $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay()->toDateTimeString() : Carbon::now()->toDateTimeString(),
            ],
            default => [Carbon::now()->subDays(30)->toDateTimeString(), Carbon::now()->toDateTimeString()],
        };
    }

    /**
     * @return array{string, string}
     */
    protected function getPreviousDateRange(): array
    {
        [$start, $end] = $this->getDateRange();
        $diff = Carbon::parse($start)->diffInSeconds(Carbon::parse($end));

        return [
            Carbon::parse($start)->subSeconds($diff)->toDateTimeString(),
            Carbon::parse($start)->toDateTimeString(),
        ];
    }

    /**
     * @return array{totalSales: int, ordersCount: int}
     */
    protected function getKpiData(string $start, string $end): array
    {
        $result = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $this->getStoreId())
            ->whereBetween('placed_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales, COUNT(*) as orders_count')
            ->first();

        return [
            'totalSales' => (int) $result->total_sales,
            'ordersCount' => (int) $result->orders_count,
        ];
    }

    protected function percentChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
