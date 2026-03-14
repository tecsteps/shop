<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Analytics</flux:heading>

        <flux:select wire:model.live="dateRange" class="w-44">
            <option value="today">Today</option>
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days">Last 30 days</option>
            <option value="custom">Custom range</option>
        </flux:select>
    </div>

    @if ($dateRange === 'custom')
        <div class="flex items-center gap-4 mb-6">
            <flux:input wire:model.live="customStartDate" type="date" label="From" />
            <flux:input wire:model.live="customEndDate" type="date" label="To" />
        </div>
    @endif

    {{-- KPI tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8" wire:loading.class="opacity-50">
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Total Revenue</flux:text>
            <flux:heading size="xl" class="mt-1">${{ $formattedTotalSales }}</flux:heading>
        </div>

        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
        </div>

        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Average Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">${{ $formattedAov }}</flux:heading>
        </div>
    </div>

    {{-- Placeholder for charts --}}
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-8 text-center">
        <flux:icon name="chart-bar" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
        <flux:heading size="lg">Sales chart</flux:heading>
        <flux:text class="mt-1">Detailed charts and visualizations will be available in a future update.</flux:text>
    </div>
</div>
