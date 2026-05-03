<div class="space-y-6">
    <div>
        <flux:heading size="xl">Analytics</flux:heading>
        <flux:text>Sales, traffic, and conversion for the active store.</flux:text>
    </div>

    <div class="flex justify-end">
        <flux:select wire:model.live="dateRange" label="Date range" class="max-w-48">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </flux:select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total sales', 'value' => $totalSales],
            ['label' => 'Orders', 'value' => $summary['orders_count']],
            ['label' => 'Average order value', 'value' => $averageOrderValue],
            ['label' => 'Conversion rate', 'value' => number_format($summary['conversion_rate'] * 100, 2).'%'],
        ] as $tile)
            <section wire:key="analytics-tile-{{ $tile['label'] }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">{{ $tile['label'] }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $tile['value'] }}</div>
            </section>
        @endforeach
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading size="lg">Daily revenue</flux:heading>
        <div class="mt-5 space-y-3">
            @foreach ($dailyMetrics as $point)
                <div wire:key="analytics-day-{{ $point['date'] }}" class="grid grid-cols-[8rem_1fr_3rem] items-center gap-4 text-sm">
                    <div class="text-zinc-500">{{ $point['date'] }}</div>
                    <div class="h-2 rounded bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-2 rounded bg-zinc-950 dark:bg-white" style="width: {{ min(100, $summary['revenue_amount'] > 0 ? ($point['revenue_amount'] / max(1, collect($dailyMetrics)->max('revenue_amount'))) * 100 : 0) }}%"></div>
                    </div>
                    <div class="text-right font-medium">{{ $point['orders_count'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['label' => 'Visits', 'value' => $summary['visits_count']],
            ['label' => 'Add to cart', 'value' => $summary['add_to_cart_count']],
            ['label' => 'Checkout started', 'value' => $summary['checkout_started_count']],
        ] as $tile)
            <section wire:key="analytics-funnel-{{ $tile['label'] }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">{{ $tile['label'] }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $tile['value'] }}</div>
            </section>
        @endforeach
    </div>
</div>
