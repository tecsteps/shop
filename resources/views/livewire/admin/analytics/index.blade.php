<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Analytics</flux:heading>
        <flux:select wire:model.live="dateRange" class="w-48">
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
            <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
        </flux:select>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500">Total Sales</flux:text>
            <flux:heading size="xl">${{ number_format($totalSales / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500">Orders</flux:text>
            <flux:heading size="xl">{{ $orderCount }}</flux:heading>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">Sales chart</flux:heading>
        <div class="flex h-48 items-center justify-center text-zinc-400 dark:text-zinc-500">
            <p class="text-sm">Chart visualization placeholder. Integrate Chart.js for detailed analytics.</p>
        </div>
    </div>
</div>
