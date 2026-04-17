<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render(): View
    {
        $query = Order::query()
            ->whereNotNull('placed_at')
            ->whereBetween('placed_at', [
                $this->dateFrom.' 00:00:00',
                $this->dateTo.' 23:59:59',
            ]);

        $totalSales = (clone $query)->sum('total_amount');
        $orderCount = (clone $query)->count();
        $averageOrderValue = $orderCount > 0 ? (int) round($totalSales / $orderCount) : 0;

        return view('livewire.admin.analytics.index', [
            'totalSales' => $totalSales,
            'orderCount' => $orderCount,
            'averageOrderValue' => $averageOrderValue,
        ]);
    }
}
