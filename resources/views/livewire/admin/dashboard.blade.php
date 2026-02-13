<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:select wire:model.live="dateRange" class="w-40">
            <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
            <flux:select.option value="7d">{{ __('Last 7 days') }}</flux:select.option>
            <flux:select.option value="30d">{{ __('Last 30 days') }}</flux:select.option>
            <flux:select.option value="all">{{ __('All time') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm">{{ __('Total Sales') }}</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($totalSales / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm">{{ __('Orders') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $orderCount }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm">{{ __('Avg. Order Value') }}</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($averageOrderValue / 100, 2) }}</flux:heading>
        </div>
    </div>

    <div class="mt-8">
        <flux:heading size="lg">{{ __('Recent Orders') }}</flux:heading>
        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>{{ __('Order') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($recentOrders as $order)
                    <flux:table.row :key="$order->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="hover:underline">
                                {{ $order->order_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $order->customer?->name ?? $order->email }}</flux:table.cell>
                        <flux:table.cell>{{ $order->placed_at?->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($order->status->value) {
                                'paid' => 'green',
                                'pending' => 'yellow',
                                'fulfilled' => 'blue',
                                'cancelled' => 'red',
                                'refunded' => 'zinc',
                                default => 'zinc',
                            }">{{ ucfirst($order->status->value) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">${{ number_format($order->total_amount / 100, 2) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text class="text-center">{{ __('No orders yet.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
