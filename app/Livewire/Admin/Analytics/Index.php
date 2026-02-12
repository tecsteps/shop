<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\AnalyticsDaily;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Analytics')]
class Index extends Component
{
    public string $period = 'last_30_days';

    public function updatedPeriod(): void
    {
        // Triggers re-render
    }

    public function render(): View
    {
        $store = app('current_store');

        $startDate = match ($this->period) {
            'last_7_days' => now()->subDays(7)->startOfDay(),
            'last_30_days' => now()->subDays(30)->startOfDay(),
            'last_90_days' => now()->subDays(90)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        $pageViews = AnalyticsDaily::query()
            ->where('store_id', $store->id)
            ->where('metric', 'page_views')
            ->where('date', '>=', $startDate)
            ->sum('value');

        $revenueData = AnalyticsDaily::query()
            ->where('store_id', $store->id)
            ->where('metric', 'revenue')
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date->format('M d'),
                'value' => $row->value,
            ])
            ->toArray();

        $totalRevenue = array_sum(array_column($revenueData, 'value'));

        $topProducts = AnalyticsDaily::query()
            ->where('store_id', $store->id)
            ->where('metric', 'product_views')
            ->where('date', '>=', $startDate)
            ->selectRaw('dimension, SUM(value) as total')
            ->groupBy('dimension')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();

        return view('livewire.admin.analytics.index', [
            'pageViews' => (int) $pageViews,
            'totalRevenue' => (int) $totalRevenue,
            'revenueData' => $revenueData,
            'topProducts' => $topProducts,
        ]);
    }
}
