<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('storefront.account.dashboard') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Back to Account</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Order History</h1>
    </div>

    @if($orders->isEmpty())
        <div class="rounded-lg border border-gray-200 p-12 text-center dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400">You have not placed any orders yet.</p>
            <a href="/" class="mt-4 inline-block rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Start Shopping
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Fulfillment</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($orders as $order)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('storefront.account.orders.show', $order->order_number) }}"
                                   class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $order->placed_at?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                    @if($order->financial_status->value === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($order->financial_status->value === 'refunded') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                    @if($order->fulfillment_status->value === 'fulfilled') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($order->fulfillment_status->value === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ ucfirst($order->fulfillment_status->value) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                {{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}
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
