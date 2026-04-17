<div class="p-6">
    <h1 class="text-2xl font-semibold tracking-tight">Orders</h1>
    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-200 text-left text-xs uppercase text-neutral-500 dark:border-neutral-800">
                    <th class="py-2">Order</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Financial</th>
                    <th class="py-2">Fulfillment</th>
                    <th class="py-2">Total</th>
                    <th class="py-2">Placed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr wire:key="order-{{ $order->id }}" class="border-b border-neutral-100 dark:border-neutral-900">
                        <td class="py-2"><a href="{{ url('/admin/orders/'.$order->id) }}" class="font-medium text-neutral-900 hover:underline dark:text-white">{{ $order->order_number }}</a></td>
                        <td class="py-2 capitalize">{{ $order->status->value }}</td>
                        <td class="py-2 capitalize">{{ str_replace('_', ' ', $order->financial_status->value) }}</td>
                        <td class="py-2 capitalize">{{ $order->fulfillment_status->value }}</td>
                        <td class="py-2">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</td>
                        <td class="py-2 text-neutral-500">{{ optional($order->placed_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-neutral-500">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
