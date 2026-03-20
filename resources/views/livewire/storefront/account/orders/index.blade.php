<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('storefront.account') }}">Account</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:heading size="xl">Order History</flux:heading>

    @if($orders->isEmpty())
        <p class="mt-6 text-zinc-500 dark:text-zinc-400">You have not placed any orders yet.</p>
    @else
        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="pb-3 pr-4 font-medium">Order</th>
                        <th class="pb-3 pr-4 font-medium">Date</th>
                        <th class="pb-3 pr-4 font-medium">Status</th>
                        <th class="pb-3 pr-4 font-medium">Total</th>
                        <th class="pb-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="py-3 pr-4 font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-400">{{ \Carbon\Carbon::parse($order->placed_at)->format('M d, Y') }}</td>
                            <td class="py-3 pr-4">
                                @php
                                    $statusColor = match($order->status) {
                                        \App\Enums\OrderStatus::Pending => 'yellow',
                                        \App\Enums\OrderStatus::Paid => 'green',
                                        \App\Enums\OrderStatus::Fulfilled => 'blue',
                                        \App\Enums\OrderStatus::Cancelled => 'zinc',
                                        \App\Enums\OrderStatus::Refunded => 'red',
                                    };
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">{{ ucfirst($order->status->value) }}</flux:badge>
                            </td>
                            <td class="py-3 pr-4 text-zinc-900 dark:text-white">${{ number_format($order->total_amount / 100, 2) }}</td>
                            <td class="py-3">
                                <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                                   class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
