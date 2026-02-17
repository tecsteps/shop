<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Account</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Welcome back, {{ $customer->name ?? $customer->email }}</p>
        </div>
        <button wire:click="logout"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            Log out
        </button>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <a href="{{ route('storefront.account.orders.index') }}"
           class="group rounded-lg border border-gray-200 p-6 transition-colors hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
            <h2 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">Orders</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View your order history and track shipments.</p>
        </a>

        <a href="{{ route('storefront.account.addresses.index') }}"
           class="group rounded-lg border border-gray-200 p-6 transition-colors hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
            <h2 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">Addresses</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your shipping and billing addresses.</p>
        </a>
    </div>

    @if($recentOrders->isNotEmpty())
        <div class="mt-10">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @foreach($recentOrders as $order)
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
                                        @if($order->status->value === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($order->status->value === 'fulfilled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($order->status->value === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        {{ ucfirst($order->status->value) }}
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
        </div>
    @endif
</div>
