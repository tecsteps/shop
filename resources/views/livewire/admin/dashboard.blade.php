<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:text>{{ $store->name }}</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Total sales</flux:text>
            <flux:heading size="lg">{{ number_format($metrics['sales_amount'] / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Orders</flux:text>
            <flux:heading size="lg">{{ $metrics['order_count'] }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">AOV</flux:text>
            <flux:heading size="lg">{{ number_format($metrics['aov_amount'] / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Conversion</flux:text>
            <flux:heading size="lg">{{ number_format($metrics['conversion_rate'] * 100, 2) }}%</flux:heading>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Recent orders</flux:heading>
        @if (count($recentOrders) === 0)
            <flux:text class="mt-3 text-zinc-500">No orders yet.</flux:text>
        @else
            <table class="mt-3 w-full text-sm">
                <thead class="text-zinc-500">
                    <tr>
                        <th class="p-2 text-left">Order</th>
                        <th class="p-2 text-left">Customer</th>
                        <th class="p-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td class="p-2">{{ $order['number'] }}</td>
                            <td class="p-2">{{ $order['customer'] }}</td>
                            <td class="p-2 text-right">{{ number_format($order['total_amount'] / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
