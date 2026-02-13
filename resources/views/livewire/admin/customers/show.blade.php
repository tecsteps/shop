<div>
    <flux:heading size="xl">{{ $customer->name }}</flux:heading>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Contact') }}</flux:heading>
                <flux:text class="mt-1">{{ $customer->email }}</flux:text>
                <flux:text class="text-sm">{{ __('Joined') }} {{ $customer->created_at->format('M d, Y') }}</flux:text>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Marketing') }}</flux:heading>
                <flux:text class="mt-1">{{ $customer->marketing_opt_in ? __('Opted in') : __('Not opted in') }}</flux:text>
            </div>
        </div>

        <div class="lg:col-span-2">
            <flux:heading size="lg">{{ __('Recent Orders') }}</flux:heading>
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>{{ __('Order') }}</flux:table.column>
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
                            <flux:table.cell>{{ $order->placed_at?->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="match($order->status->value) {
                                    'paid' => 'green', 'pending' => 'yellow', 'fulfilled' => 'blue',
                                    'cancelled' => 'red', 'refunded' => 'zinc', default => 'zinc',
                                }">{{ ucfirst($order->status->value) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">${{ number_format($order->total_amount / 100, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <flux:text class="text-center">{{ __('No orders yet.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
