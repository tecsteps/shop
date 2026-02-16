<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order History</h1>
        <a href="{{ url('/account') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Back to account</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-6 py-4 text-sm"><a href="{{ url('/account/orders/' . ltrim($order->order_number, '#')) }}" class="font-medium text-gray-900 hover:underline dark:text-white">{{ $order->order_number }}</a></td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                        <td class="px-6 py-4"><flux:badge size="sm">{{ ucfirst($order->financial_status?->value ?? 'unknown') }}</flux:badge></td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($order->total / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
