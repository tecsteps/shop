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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8" wire:loading.class="opacity-50">
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

        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Visits</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($visitsCount) }}</flux:heading>
        </div>
    </div>

    {{-- Conversion Funnel --}}
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6 mb-8">
        <flux:heading size="lg" class="mb-4">Conversion Funnel</flux:heading>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Visits</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format($visitsCount) }}</flux:heading>
            </div>
            <div class="text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Add to Cart</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format($addToCartCount) }}</flux:heading>
            </div>
            <div class="text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Checkout Started</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format($checkoutStartedCount) }}</flux:heading>
            </div>
            <div class="text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Completed</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format($checkoutCompletedCount) }}</flux:heading>
                <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">{{ $conversionRate }}% rate</flux:text>
            </div>
        </div>
    </div>

    {{-- Sales Chart Data --}}
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
        <flux:heading size="lg" class="mb-4">Daily Sales</flux:heading>
        @if (count($chartData) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-2 px-3 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="text-right py-2 px-3 font-medium text-zinc-500 dark:text-zinc-400">Revenue</th>
                            <th class="text-right py-2 px-3 font-medium text-zinc-500 dark:text-zinc-400">Orders</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chartData as $day)
                            <tr wire:key="chart-{{ $day['date'] }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 px-3">{{ $day['date'] }}</td>
                                <td class="py-2 px-3 text-right">${{ number_format($day['revenue'] / 100, 2) }}</td>
                                <td class="py-2 px-3 text-right">{{ $day['orders'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <flux:icon name="chart-bar" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
                <flux:text>No analytics data available for the selected period.</flux:text>
                <flux:text class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Data is aggregated daily. Browse the storefront to generate events.</flux:text>
            </div>
        @endif
    </div>
</div>
