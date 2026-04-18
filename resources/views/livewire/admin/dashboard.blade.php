<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Dashboard</flux:heading>
        <div class="flex items-center gap-2">
            <flux:input type="date" wire:model.live="startDate" label="From" size="sm" />
            <flux:input type="date" wire:model.live="endDate" label="To" size="sm" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Total sales</flux:text>
            <flux:heading size="lg" data-testid="metric-sales">{{ number_format($metrics['sales_amount'] / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Orders</flux:text>
            <flux:heading size="lg" data-testid="metric-orders">{{ $metrics['order_count'] }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">AOV</flux:text>
            <flux:heading size="lg" data-testid="metric-aov">{{ number_format($metrics['aov_amount'] / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Conversion</flux:text>
            <flux:heading size="lg" data-testid="metric-conversion">{{ number_format($metrics['conversion_rate'] * 100, 2) }}%</flux:heading>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Recent orders</flux:heading>
        @if ($recentOrders->isEmpty())
            <flux:text class="mt-3 text-zinc-500">No orders yet.</flux:text>
        @else
            <table class="mt-3 w-full text-sm">
                <thead class="text-zinc-500">
                    <tr>
                        <th class="p-2 text-left">Order</th>
                        <th class="p-2 text-left">Email</th>
                        <th class="p-2 text-left">Status</th>
                        <th class="p-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr wire:key="recent-order-{{ $order->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="p-2">
                                @if (Route::has('admin.orders.show'))
                                    <a class="text-sky-600 hover:underline" href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a>
                                @else
                                    #{{ $order->order_number }}
                                @endif
                            </td>
                            <td class="p-2">{{ $order->email }}</td>
                            <td class="p-2">{{ $order->financial_status?->value }}</td>
                            <td class="p-2 text-right">{{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
