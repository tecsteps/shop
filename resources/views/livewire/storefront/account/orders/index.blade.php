<div class="space-y-8">
    <header class="space-y-2">
        <a href="{{ route('storefront.account.dashboard') }}" class="text-xs text-zinc-500 underline-offset-2 hover:underline dark:text-zinc-400">Back to account</a>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Orders</h1>
    </header>

    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">You have not placed any orders yet.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ $order->order_number }}</td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $order->status?->value ?? 'pending' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" class="text-xs font-medium text-zinc-700 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-300 dark:hover:text-zinc-100">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>
            {{ $orders->links() }}
        </div>
    @endif
</div>
