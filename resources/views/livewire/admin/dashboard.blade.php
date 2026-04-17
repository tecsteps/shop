<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:text class="text-neutral-500">Revenue today</flux:text>
            <flux:heading size="lg">{{ number_format($revenueToday / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:text class="text-neutral-500">Orders today</flux:text>
            <flux:heading size="lg">{{ $ordersCount }}</flux:heading>
        </div>
        <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:text class="text-neutral-500">Average order value</flux:text>
            <flux:heading size="lg">{{ number_format($aov / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:text class="text-neutral-500">Visits today</flux:text>
            <flux:heading size="lg">{{ $visitsToday }}</flux:heading>
        </div>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
            <flux:heading size="lg">Recent orders</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-neutral-500">
                    <tr>
                        <th class="px-4 py-2">Order</th>
                        <th class="px-4 py-2">Customer</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Total</th>
                        <th class="px-4 py-2">Placed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr wire:key="recent-order-{{ $order->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                            <td class="px-4 py-2">
                                <a href="{{ url('/admin/orders/'.$order->id) }}" class="font-medium hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-4 py-2">{{ $order->email ?? 'Guest' }}</td>
                            <td class="px-4 py-2">
                                <flux:badge size="sm">{{ str_replace('_', ' ', $order->financial_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-2">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</td>
                            <td class="px-4 py-2 text-neutral-500">{{ optional($order->placed_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-neutral-500">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
