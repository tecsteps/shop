<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl" level="1">Analytics</flux:heading>

        <flux:select wire:model.live="dateRange" class="w-48">
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
            <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
            <flux:select.option value="custom">Custom range</flux:select.option>
        </flux:select>
    </div>

    @if($dateRange === 'custom')
        <div class="mb-6 flex gap-4">
            <flux:input type="date" wire:model.live.debounce.500ms="customStartDate" label="Start date" />
            <flux:input type="date" wire:model.live.debounce.500ms="customEndDate" label="End date" />
        </div>
    @endif

    {{-- KPI Tiles --}}
    @php $kpis = $this->kpis; @endphp
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formatCurrency($kpis['totalSales']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($kpis['ordersCount']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formatCurrency($kpis['averageOrderValue']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Conversion Rate</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($kpis['conversionRate'], 1) }}%</flux:heading>
        </div>
    </div>

    {{-- Sales Chart --}}
    @php $chartData = $this->salesChartData; @endphp
    <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">Daily Revenue</flux:heading>
        @if(count($chartData['labels']) > 0)
            <div class="space-y-2">
                @foreach($chartData['labels'] as $index => $label)
                    @php
                        $revenue = $chartData['revenue'][$index] ?? 0;
                        $maxRevenue = max(1, max($chartData['revenue']));
                        $widthPercent = ($revenue / $maxRevenue) * 100;
                    @endphp
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-24 shrink-0 text-zinc-500">{{ $label }}</span>
                        <div class="flex-1">
                            <div class="h-6 rounded bg-blue-500" style="width: {{ $widthPercent }}%"></div>
                        </div>
                        <span class="w-20 shrink-0 text-right font-medium">{{ $this->formatCurrency($revenue) }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <flux:text class="text-zinc-500">No data for this period.</flux:text>
        @endif
    </div>

    {{-- Metrics Summary --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">Funnel</flux:heading>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:text>Visits</flux:text>
                <flux:text class="font-medium">{{ number_format($kpis['visitsCount']) }}</flux:text>
            </div>
            <div class="flex items-center justify-between">
                <flux:text>Orders</flux:text>
                <flux:text class="font-medium">{{ number_format($kpis['ordersCount']) }}</flux:text>
            </div>
            <div class="flex items-center justify-between">
                <flux:text>Conversion Rate</flux:text>
                <flux:text class="font-medium">{{ number_format($kpis['conversionRate'], 1) }}%</flux:text>
            </div>
        </div>
    </div>
</div>
