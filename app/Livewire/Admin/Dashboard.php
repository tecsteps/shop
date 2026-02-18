<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public string $dateRange = 'last_30_days';

    public int $totalSales = 0;

    public int $ordersCount = 0;

    public int $averageOrderValue = 0;

    public function mount(): void
    {
        $this->loadKpis();
    }

    public function updatedDateRange(): void
    {
        $this->loadKpis();
    }

    public function loadKpis(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        [$start, $end] = $this->getDateRange();

        $query = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotNull('placed_at')
            ->whereBetween('placed_at', [$start, $end]);

        $this->totalSales = (int) (clone $query)->sum('total_amount');
        $this->ordersCount = (clone $query)->count();
        $this->averageOrderValue = $this->ordersCount > 0
            ? (int) round($this->totalSales / $this->ordersCount)
            : 0;
    }

    /**
     * @return array{Carbon, Carbon}
     */
    protected function getDateRange(): array
    {
        $end = now()->endOfDay();

        return match ($this->dateRange) {
            'today' => [now()->startOfDay(), $end],
            'last_7_days' => [now()->subDays(7)->startOfDay(), $end],
            default => [now()->subDays(30)->startOfDay(), $end],
        };
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $recentOrders = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotNull('placed_at')
            ->with('customer')
            ->latest('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'recentOrders' => $recentOrders,
        ]);
    }
}
