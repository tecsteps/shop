<div class="space-y-6">
    <flux:heading size="xl">Orders</flux:heading>

    <div class="flex flex-wrap items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order number or email..." />
        <flux:select wire:model.live="statusFilter">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="fulfilled">Fulfilled</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
        </flux:select>
        <flux:input wire:model.live="dateFrom" type="date" />
        <flux:input wire:model.live="dateTo" type="date" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Order</th>
                    <th class="px-4 py-3 font-medium">Customer</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Payment</th>
                    <th class="px-4 py-3 font-medium">Fulfillment</th>
                    <th class="px-4 py-3 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr wire:key="order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $order->customer?->name ?? $order->email }}</td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($order->status->value) }}</flux:badge></td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($order->financial_status->value) }}</flux:badge></td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge></td>
                        <td class="px-4 py-3 text-right">${{ number_format($order->total_amount / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $orders->links() }}</div>
</div>
