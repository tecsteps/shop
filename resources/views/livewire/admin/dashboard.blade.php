<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <flux:heading size="xl">Dashboard</flux:heading>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="dateRange" class="w-auto">
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
                <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
                <flux:select.option value="custom">Custom range</flux:select.option>
            </flux:select>

            @if ($dateRange === 'custom')
                <flux:input type="date" wire:model.live.debounce.500ms="customStartDate" />
                <flux:input type="date" wire:model.live.debounce.500ms="customEndDate" />
            @endif
        </div>
    </div>

    {{-- KPI Tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8" wire:loading.class="opacity-50">
        {{-- Total Sales --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedTotalSales }}</flux:heading>
            <div class="mt-2 flex items-center gap-1">
                @if ($salesChange >= 0)
                    <flux:badge color="green" size="sm">+{{ $salesChange }}%</flux:badge>
                    <flux:icon name="arrow-up" class="size-3 text-green-600" />
                @else
                    <flux:badge color="red" size="sm">{{ $salesChange }}%</flux:badge>
                    <flux:icon name="arrow-down" class="size-3 text-red-600" />
                @endif
            </div>
        </div>

        {{-- Orders --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
            <div class="mt-2 flex items-center gap-1">
                @if ($ordersChange >= 0)
                    <flux:badge color="green" size="sm">+{{ $ordersChange }}%</flux:badge>
                    <flux:icon name="arrow-up" class="size-3 text-green-600" />
                @else
                    <flux:badge color="red" size="sm">{{ $ordersChange }}%</flux:badge>
                    <flux:icon name="arrow-down" class="size-3 text-red-600" />
                @endif
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedAov }}</flux:heading>
            <div class="mt-2 flex items-center gap-1">
                @if ($aovChange >= 0)
                    <flux:badge color="green" size="sm">+{{ $aovChange }}%</flux:badge>
                    <flux:icon name="arrow-up" class="size-3 text-green-600" />
                @else
                    <flux:badge color="red" size="sm">{{ $aovChange }}%</flux:badge>
                    <flux:icon name="arrow-down" class="size-3 text-red-600" />
                @endif
            </div>
        </div>

        {{-- Placeholder for visitors --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">Visitors</flux:text>
            <flux:heading size="xl" class="mt-1">-</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm">N/A</flux:badge>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg">Recent orders</flux:heading>
        </div>

        @if (count($recentOrders) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Order</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Customer</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-right px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            <th class="text-right px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">
                                    #{{ $order['order_number'] }}
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $order['email'] }}
                                </td>
                                <td class="px-6 py-3">
                                    <flux:badge size="sm" :color="match($order['status']) {
                                        'paid' => 'green',
                                        'pending' => 'yellow',
                                        'refunded' => 'red',
                                        'partially_refunded' => 'orange',
                                        default => 'zinc'
                                    }">
                                        {{ str_replace('_', ' ', ucfirst($order['status'])) }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-3 text-right text-zinc-900 dark:text-white">
                                    ${{ number_format($order['total_amount'] / 100, 2) }}
                                </td>
                                <td class="px-6 py-3 text-right text-zinc-500 dark:text-zinc-400">
                                    {{ $order['placed_at'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="shopping-bag" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">No orders yet.</flux:text>
            </div>
        @endif
    </div>
</div>
