<div class="space-y-4">
    <flux:heading size="xl">Orders</flux:heading>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search order # or email..." />
        <flux:select wire:model.live="status" placeholder="All statuses">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($statuses as $case)
                <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="startDate" label="From" size="sm" />
        <flux:input type="date" wire:model.live="endDate" label="To" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Order</th>
                    <th class="p-3">Date</th>
                    <th class="p-3">Customer</th>
                    <th class="p-3">Financial</th>
                    <th class="p-3">Fulfillment</th>
                    <th class="p-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr wire:key="order-{{ $order->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3">
                            <a class="text-sky-600 hover:underline" href="{{ route('admin.orders.show', $order) }}" wire:navigate>#{{ $order->order_number }}</a>
                        </td>
                        <td class="p-3">{{ $order->placed_at?->format('Y-m-d H:i') }}</td>
                        <td class="p-3">{{ $order->email }}</td>
                        <td class="p-3">{{ $order->financial_status?->value }}</td>
                        <td class="p-3">{{ $order->fulfillment_status?->value }}</td>
                        <td class="p-3 text-right">{{ number_format($order->total_amount / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="6">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $orders->links() }}</div>
</div>
