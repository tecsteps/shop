<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Services\AnalyticsService;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public string $dateRange = '30';

    public function render(): View
    {
        $to = now()->toDateString();
        $from = now()->subDays((int) $this->dateRange)->toDateString();
        $data = app(AnalyticsService::class)->summary($this->currentStore(), $from, $to);
        $summary = $data['summary'];

        return view('livewire.admin.analytics.index', [
            'period' => $data['period'],
            'summary' => $summary,
            'dailyMetrics' => collect($data['daily'])->take(-14)->values(),
            'totalSales' => $this->money((int) $summary['revenue_amount']),
            'averageOrderValue' => $this->money((int) $summary['aov_amount']),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Analytics',
        ]);
    }
}
