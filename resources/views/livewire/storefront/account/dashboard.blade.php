<div>
    <x-slot:title>My Account</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">My Account</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Welcome back, {{ $customer->name }}</p>

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('customer.orders') }}" class="group rounded-lg border border-gray-200 p-6 hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                <h2 class="text-lg font-semibold group-hover:text-blue-600">Orders</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View your order history</p>
            </a>

            <a href="{{ route('customer.addresses') }}" class="group rounded-lg border border-gray-200 p-6 hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                <h2 class="text-lg font-semibold group-hover:text-blue-600">Addresses</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your saved addresses</p>
            </a>

            <button wire:click="logout" class="group rounded-lg border border-gray-200 p-6 text-left hover:border-red-500 dark:border-gray-700 dark:hover:border-red-500">
                <h2 class="text-lg font-semibold group-hover:text-red-600">Log Out</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Sign out of your account</p>
            </button>
        </div>

        @if ($recentOrders->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-xl font-bold">Recent Orders</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($recentOrders as $order)
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
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $order->status->value === 'pending',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => in_array($order->status->value, ['paid', 'fulfilled']),
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $order->status->value === 'cancelled',
                                        ])>
                                            {{ ucfirst($order->status->value) }}
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
                <div class="mt-4">
                    <a href="{{ route('customer.orders') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        View all orders &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
