<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $customer->name ?? $customer->email }}</flux:heading>
        <flux:button :href="route('admin.customers.index')" variant="ghost" wire:navigate>Back</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Contact</flux:heading>
                <div class="mt-3 space-y-1 text-sm">
                    <div>{{ $customer->email }}</div>
                    <div class="text-zinc-500">Member since {{ $customer->created_at?->format('M d, Y') }}</div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Addresses</flux:heading>
                @if ($customer->addresses->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500">No addresses on file.</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach ($customer->addresses as $address)
                            <div class="text-sm">
                                <div>{{ $address->first_name }} {{ $address->last_name }}</div>
                                <div class="text-zinc-500">{{ $address->address1 }}, {{ $address->city }}, {{ $address->country_code }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Recent orders</flux:heading>
                @if ($customer->orders->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500">No orders yet.</p>
                @else
                    <flux:table class="mt-4">
                        <flux:table.columns>
                            <flux:table.column>Order</flux:table.column>
                            <flux:table.column>Total</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Date</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($customer->orders as $order)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline" wire:navigate>{{ $order->order_number }}</a>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ number_format($order->total_amount / 100, 2) }}</flux:table.cell>
                                    <flux:table.cell><flux:badge size="sm">{{ $order->financial_status->value }}</flux:badge></flux:table.cell>
                                    <flux:table.cell>{{ $order->placed_at?->format('M d, Y') }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Lifetime stats</flux:heading>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-zinc-500">Orders</dt>
                        <dd class="text-lg font-semibold">{{ $stats['orders_count'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Total spent</dt>
                        <dd class="text-lg font-semibold">{{ number_format($stats['total_spent'] / 100, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Average order</dt>
                        <dd class="text-lg font-semibold">{{ number_format($stats['average'] / 100, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
