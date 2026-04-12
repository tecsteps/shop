<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:select wire:model.live="period" class="w-40">
            <flux:select.option value="7d">Last 7 days</flux:select.option>
            <flux:select.option value="30d">Last 30 days</flux:select.option>
            <flux:select.option value="90d">Last 90 days</flux:select.option>
        </flux:select>
    </div>

    @php($kpis = $this->kpis)

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total sales</div>
            <div class="mt-2 text-2xl font-semibold" data-test="kpi-total-sales">
                {{ number_format($kpis['total_sales'] / 100, 2) }}
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Orders</div>
            <div class="mt-2 text-2xl font-semibold" data-test="kpi-orders-count">
                {{ $kpis['orders_count'] }}
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Average order value</div>
            <div class="mt-2 text-2xl font-semibold" data-test="kpi-aov">
                {{ number_format($kpis['aov'] / 100, 2) }}
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Conversion rate</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-400">N/A</div>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Sales over time</flux:heading>
        <div class="mt-4 flex h-48 items-center justify-center rounded-md bg-zinc-50 text-sm text-zinc-400 dark:bg-zinc-800">
            Charts coming soon
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Recent orders</flux:heading>
        <div class="mt-4">
            @if ($this->recentOrders->isEmpty())
                <p class="text-sm text-zinc-500">No orders yet.</p>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column>Customer</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentOrders as $order)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>
                                        {{ $order->order_number }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $order->email }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$order->financial_status->value === 'paid' ? 'green' : ($order->financial_status->value === 'pending' ? 'yellow' : 'zinc')">
                                        {{ $order->financial_status->value }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </div>
</div>
