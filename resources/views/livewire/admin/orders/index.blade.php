<div class="flex flex-col gap-6">
    <flux:heading size="xl">Orders</flux:heading>

    <flux:select wire:model.live="status" class="w-fit">
        <flux:select.option value="all">All</flux:select.option>
        <flux:select.option value="pending">Pending</flux:select.option>
        <flux:select.option value="authorized">Authorized</flux:select.option>
        <flux:select.option value="paid">Paid</flux:select.option>
        <flux:select.option value="refunded">Refunded</flux:select.option>
    </flux:select>

    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($orders->isEmpty())
            <div class="p-10 text-center text-zinc-500">No orders yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Payment</flux:table.column>
                    <flux:table.column>Fulfillment</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($orders as $order)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium hover:underline" wire:navigate>{{ $order->order_number }}</a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->placed_at?->format('Y-m-d H:i') }}</flux:table.cell>
                            <flux:table.cell>{{ $order->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$order->financial_status->value === 'paid' ? 'emerald' : 'zinc'">{{ $order->financial_status->value }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$order->fulfillment_status->value === 'fulfilled' ? 'emerald' : 'zinc'">{{ $order->fulfillment_status->value }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <div>{{ $orders->links() }}</div>
</div>
