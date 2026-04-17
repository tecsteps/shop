<div>
    <x-slot:title>Dashboard</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:select wire:model.live="dateRange" class="w-40">
            <option value="today">Today</option>
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days">Last 30 days</option>
        </flux:select>
    </div>

    {{-- KPI Tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:text class="text-sm text-gray-500">Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">
                {{ number_format($totalSales / 100, 2, '.', ',') }} {{ app('current_store')->default_currency }}
            </flux:heading>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:text class="text-sm text-gray-500">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:text class="text-sm text-gray-500">Avg Order Value</flux:text>
            <flux:heading size="xl" class="mt-1">
                {{ number_format($averageOrderValue / 100, 2, '.', ',') }} {{ app('current_store')->default_currency }}
            </flux:heading>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="mt-8">
        <flux:heading size="lg">Recent Orders</flux:heading>
        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $order->customer?->name ?? $order->email ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="match($order->status->value) { 'pending' => 'yellow', 'paid' => 'green', 'fulfilled' => 'green', 'cancelled' => 'red', default => 'zinc' }">
                                    {{ ucfirst($order->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="match($order->financial_status->value) { 'pending' => 'yellow', 'paid' => 'green', 'refunded' => 'red', 'voided' => 'red', default => 'zinc' }">
                                    {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                {{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->placed_at?->format('M d, Y') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No orders found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
