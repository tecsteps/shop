<?php

namespace App\Livewire\Admin\Analytics;

use App\Services\AnalyticsService;
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
        $service = app(AnalyticsService::class);

        $endDate = now()->toDateString();

        $startDate = match ($this->period) {
            'last_7_days' => now()->subDays(7)->toDateString(),
            'last_30_days' => now()->subDays(30)->toDateString(),
            'last_90_days' => now()->subDays(90)->toDateString(),
            default => now()->subDays(30)->toDateString(),
        };

        $dailyMetrics = $service->getDailyMetrics($store, $startDate, $endDate);

        $revenueData = [];
        $totalRevenue = 0;
        $totalPageViews = 0;
        $totalOrders = 0;
        $totalVisits = 0;

        foreach ($dailyMetrics as $date => $metrics) {
            foreach ($metrics as $metric) {
                match ($metric->metric) {
                    'revenue_amount' => $totalRevenue += $metric->value,
                    'page_views' => $totalPageViews += $metric->value,
                    'orders_count' => $totalOrders += $metric->value,
                    'visits_count' => $totalVisits += $metric->value,
                    default => null,
                };

                if ($metric->metric === 'revenue_amount') {
                    $revenueData[] = [
                        'date' => $metric->date->format('M d'),
                        'value' => $metric->value,
                    ];
                }
            }
        }

        $funnel = $service->getConversionFunnel($store, $startDate, $endDate);
        $topProducts = $service->getTopProducts($store, $startDate, $endDate);

        return view('livewire.admin.analytics.index', [
            'pageViews' => $totalPageViews,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalVisits' => $totalVisits,
            'revenueData' => $revenueData,
            'revenueChartJson' => json_encode($revenueData),
            'funnel' => $funnel,
            'topProducts' => $topProducts,
        ]);
    }
}
