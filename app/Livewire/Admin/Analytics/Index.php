<?php

namespace App\Livewire\Admin\Analytics;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesAdminStore;

    public string $dateRange = 'last_30_days';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public string $channelFilter = 'all';

    public string $deviceFilter = 'all';

    public bool $isExporting = false;

    public ?string $exportUrl = null;

    public function mount(): void
    {
        Gate::authorize('view-analytics');
    }

    public function updatedDateRange(): void
    {
        $this->customStartDate = null;
        $this->customEndDate = null;
    }

    public function exportCsv(): void
    {
        Gate::authorize('view-analytics');

        $this->isExporting = true;
        $this->exportUrl = null;

        $store = $this->store();
        [$from, $to] = $this->resolveRange();
        $summary = app(AnalyticsService::class)->summary($store, $from, $to);

        $lines = ['date,orders_count,revenue_amount,aov_amount,visits_count,add_to_cart_count,checkout_started_count'];

        foreach ($summary['daily'] as $row) {
            $lines[] = implode(',', [
                $row['date'],
                $row['orders_count'],
                $row['revenue_amount'],
                $row['aov_amount'],
                $row['visits_count'],
                $row['add_to_cart_count'],
                $row['checkout_started_count'],
            ]);
        }

        $path = 'exports/analytics-'.$store->getKey().'-'.$from.'-'.$to.'.csv';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, implode("\n", $lines)."\n");

        $this->isExporting = false;
        $this->exportUrl = $path;
        $this->toast('success', 'Export ready.');
    }

    public function render()
    {
        $store = $this->store();
        $analytics = app(AnalyticsService::class);
        [$from, $to] = $this->resolveRange();
        $summary = $analytics->summary($store, $from, $to);

        $topProducts = $analytics->topProductsByRevenue($store, $from, $to, 20);
        $totalRevenue = array_sum(array_column($topProducts, 'revenue_amount'));

        return view('livewire.admin.analytics.index', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary['summary'],
            'daily' => $summary['daily'],
            'topProducts' => $topProducts,
            'totalRevenue' => $totalRevenue,
            'topReferrers' => $analytics->topReferrers($store, $from, $to),
            'funnelData' => $analytics->funnel($store, $from, $to),
        ])->layout('livewire.admin.layout.app', ['title' => 'Analytics']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveRange(): array
    {
        $today = CarbonImmutable::today();

        if ($this->dateRange === 'custom' && $this->customStartDate && $this->customEndDate) {
            return [$this->customStartDate, $this->customEndDate];
        }

        return match ($this->dateRange) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'last_7_days' => [$today->subDays(6)->toDateString(), $today->toDateString()],
            default => [$today->subDays(29)->toDateString(), $today->toDateString()],
        };
    }
}
