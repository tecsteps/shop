<?php

namespace App\Livewire\Admin\Analytics;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->endDate = now()->format('Y-m-d');
        $this->startDate = now()->subDays(29)->format('Y-m-d');
    }

    public function render()
    {
        $store = app('current_store');

        $metrics = collect();

        if (class_exists(\App\Services\AnalyticsService::class) && Schema::hasTable('analytics_daily')) {
            $service = app(\App\Services\AnalyticsService::class);
            $metrics = $service->getDailyMetrics($store, $this->startDate, $this->endDate);
        }

        $totals = [
            'revenue_amount' => (int) $metrics->sum('revenue_amount'),
            'orders_count' => (int) $metrics->sum('orders_count'),
            'visits_count' => (int) $metrics->sum('visits_count'),
            'aov_amount' => 0,
        ];
        if ($totals['orders_count'] > 0) {
            $totals['aov_amount'] = intdiv($totals['revenue_amount'], $totals['orders_count']);
        }

        return view('livewire.admin.analytics.index', [
            'metrics' => $metrics,
            'totals' => $totals,
        ]);
    }
}
