<div class="space-y-6">
    <flux:heading size="xl">Analytics</flux:heading>

    <div class="flex items-center gap-4">
        <flux:input wire:model.live="dateFrom" type="date" label="From" />
        <flux:input wire:model.live="dateTo" type="date" label="To" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Sales</flux:text>
            <p class="mt-1 text-2xl font-bold">${{ number_format($totalSales / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Orders</flux:text>
            <p class="mt-1 text-2xl font-bold">{{ $orderCount }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Average Order Value</flux:text>
            <p class="mt-1 text-2xl font-bold">${{ number_format($averageOrderValue / 100, 2) }}</p>
        </div>
    </div>

    {{-- Sales Chart Placeholder --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">Sales Over Time</flux:heading>
        <div class="flex h-64 items-center justify-center text-zinc-400 dark:text-zinc-500">
            Chart placeholder - integrate with a charting library
        </div>
    </div>
</div>
