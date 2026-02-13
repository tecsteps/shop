<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Order History</flux:heading>
        <a href="{{ route('storefront.account.dashboard') }}">
            <flux:button variant="ghost" size="sm">Back to Account</flux:button>
        </a>
    </div>

    @if ($orders->isEmpty())
        <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
            <flux:text>You have no orders yet.</flux:text>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800">
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Payment</th>
                        <th class="px-4 py-3 font-medium">Fulfillment</th>
                        <th class="px-4 py-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-4 py-3">
                                <a href="{{ route('storefront.account.orders.show', $order->order_number) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $order->placed_at?->format('M d, Y') ?? $order->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm">{{ ucfirst($order->financial_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm">{{ ucfirst($order->fulfillment_status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">${{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
