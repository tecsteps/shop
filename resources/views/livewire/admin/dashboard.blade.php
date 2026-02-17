<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>

        <flux:select wire:model.live="dateRange" class="w-48">
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
            <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
            <flux:select.option value="custom">Custom range</flux:select.option>
        </flux:select>
    </div>

    @if ($dateRange === 'custom')
        <div class="mb-6 flex gap-4">
            <flux:input type="date" wire:model.live.debounce.500ms="customStartDate" label="Start date" />
            <flux:input type="date" wire:model.live.debounce.500ms="customEndDate" label="End date" />
        </div>
    @endif

    {{-- KPI tiles --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        <x-admin.kpi-tile label="Total Sales" :value="$this->formattedTotalSales()" :change="$salesChange" />
        <x-admin.kpi-tile label="Orders" :value="(string) $ordersCount" :change="$ordersChange" />
        <x-admin.kpi-tile label="Avg Order Value" :value="$this->formattedAov()" :change="$aovChange" />
        <x-admin.kpi-tile label="Visitors" value="--" :change="0" />
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Orders chart placeholder --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Orders over time</flux:heading>
            <div class="flex h-48 items-center justify-center text-zinc-400 dark:text-zinc-500">
                <p class="text-sm">Chart data available ({{ count($ordersChartData) }} days)</p>
            </div>
        </div>

        {{-- Top products --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Top products</flux:heading>
            @if (count($topProducts) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <th class="pb-2 font-medium">Product</th>
                            <th class="pb-2 text-right font-medium">Sold</th>
                            <th class="pb-2 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $product)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                <td class="py-2 text-zinc-900 dark:text-zinc-100">{{ $product['title'] }}</td>
                                <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">{{ $product['units_sold'] }}</td>
                                <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">${{ number_format($product['revenue'] / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500">No sales data for this period.</p>
            @endif
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">Recent orders</flux:heading>
        @php
            $store = app('current_store');
            $recentOrders = \App\Models\Order::query()
                ->where('store_id', $store->id)
                ->with('customer')
                ->latest('placed_at')
                ->limit(10)
                ->get();
        @endphp

        @if ($recentOrders->isNotEmpty())
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <th class="pb-2 font-medium">Order</th>
                        <th class="pb-2 font-medium">Date</th>
                        <th class="pb-2 font-medium">Customer</th>
                        <th class="pb-2 font-medium">Status</th>
                        <th class="pb-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="py-2">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="py-2 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, g:i A') ?? '--' }}</td>
                            <td class="py-2 text-zinc-600 dark:text-zinc-400">{{ $order->customer?->name ?? 'Guest' }}</td>
                            <td class="py-2">
                                <flux:badge size="sm" :variant="$order->financial_status->value === 'paid' ? 'pill' : 'outline'">
                                    {{ ucfirst($order->financial_status->value) }}
                                </flux:badge>
                            </td>
                            <td class="py-2 text-right text-zinc-900 dark:text-zinc-100">${{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-zinc-400 dark:text-zinc-500">No orders yet.</p>
        @endif
    </div>
</div>
