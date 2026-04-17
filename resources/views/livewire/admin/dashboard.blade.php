<div class="space-y-6">
    <flux:heading size="xl">Dashboard</flux:heading>

    {{-- Date Range Filter --}}
    <div class="flex items-center gap-4">
        <flux:input wire:model.live="dateFrom" type="date" label="From" />
        <flux:input wire:model.live="dateTo" type="date" label="To" />
    </div>

    {{-- KPI Tiles --}}
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

    {{-- Recent Orders --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">Recent Orders</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-6 py-3 font-medium">Order</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-6 py-3">{{ $order->customer?->name ?? $order->email }}</td>
                            <td class="px-6 py-3"><flux:badge size="sm">{{ ucfirst($order->status->value) }}</flux:badge></td>
                            <td class="px-6 py-3 text-right">${{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
