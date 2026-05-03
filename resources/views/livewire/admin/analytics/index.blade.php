<div class="space-y-6">
    <div>
        <flux:heading size="xl">Analytics</flux:heading>
        <flux:text>Sales and order movement for the active store.</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total sales', 'value' => $totalSales],
            ['label' => 'Paid orders', 'value' => $paidOrders],
            ['label' => 'Pending orders', 'value' => $pendingOrders],
            ['label' => 'Refunded orders', 'value' => $refundedOrders],
        ] as $tile)
            <section wire:key="analytics-tile-{{ $tile['label'] }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">{{ $tile['label'] }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $tile['value'] }}</div>
            </section>
        @endforeach
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading size="lg">Daily orders</flux:heading>
        <div class="mt-5 space-y-3">
            @foreach ($dailyOrders as $point)
                <div wire:key="analytics-day-{{ $point['date'] }}" class="grid grid-cols-[8rem_1fr_3rem] items-center gap-4 text-sm">
                    <div class="text-zinc-500">{{ $point['date'] }}</div>
                    <div class="h-2 rounded bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-2 rounded bg-zinc-950 dark:bg-white" style="width: {{ min(100, $point['count'] * 25) }}%"></div>
                    </div>
                    <div class="text-right font-medium">{{ $point['count'] }}</div>
                </div>
            @endforeach
        </div>
    </section>
</div>
