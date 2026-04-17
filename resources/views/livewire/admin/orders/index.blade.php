<div class="space-y-6 p-6">
    <flux:heading size="xl">Orders</flux:heading>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search orders..." icon="magnifying-glass" class="w-72" />
        <flux:select wire:model.live="financialFilter" class="w-44">
            <flux:select.option value="">All payments</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="paid">Paid</flux:select.option>
            <flux:select.option value="refunded">Refunded</flux:select.option>
            <flux:select.option value="partially_refunded">Partially refunded</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="fulfillmentFilter" class="w-44">
            <flux:select.option value="">All fulfillment</flux:select.option>
            <flux:select.option value="unfulfilled">Unfulfilled</flux:select.option>
            <flux:select.option value="partial">Partial</flux:select.option>
            <flux:select.option value="fulfilled">Fulfilled</flux:select.option>
        </flux:select>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if ($orders->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">No orders found.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column>Payment</flux:table.column>
                    <flux:table.column>Fulfillment</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($orders as $order)
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
                            <flux:table.cell>
                                <flux:badge :color="$order->fulfillment_status->value === 'fulfilled' ? 'green' : 'zinc'">
                                    {{ $order->fulfillment_status->value }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->placed_at?->format('M d, Y') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="p-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
