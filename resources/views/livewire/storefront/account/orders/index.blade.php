<div>
    <x-slot:title>My Orders</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">My Orders</h1>
            <a href="{{ route('customer.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-500">&larr; Back to Account</a>
        </div>

        @if ($orders->isEmpty())
            <div class="mt-8 rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No orders found.</p>
                <a href="/collections" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-500">
                    Start Shopping
                </a>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Fulfillment</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <a href="{{ route('customer.orders.show', $order->order_number) }}" class="font-medium text-blue-600 hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $order->placed_at?->format('M d, Y') ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $order->financial_status->value === 'pending',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $order->financial_status->value === 'paid',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => in_array($order->financial_status->value, ['refunded', 'voided']),
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $order->financial_status->value === 'partially_refunded',
                                    ])>
                                        {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => $order->fulfillment_status->value === 'unfulfilled',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $order->fulfillment_status->value === 'partial',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $order->fulfillment_status->value === 'fulfilled',
                                    ])>
                                        {{ ucfirst($order->fulfillment_status->value) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
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
</div>
