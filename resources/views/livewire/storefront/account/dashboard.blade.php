@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
@endphp

<div>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Welcome back, {{ $customer?->name ?? 'Guest' }}!
        </h1>

        {{-- Quick Links --}}
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <a href="{{ route('storefront.account.orders') }}"
               class="rounded-lg border border-gray-200 p-6 transition-shadow hover:shadow-md dark:border-gray-800">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <h3 class="mt-3 font-semibold text-gray-900 dark:text-white">Order history</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View all your orders</p>
            </a>

            <a href="{{ route('storefront.account.addresses') }}"
               class="rounded-lg border border-gray-200 p-6 transition-shadow hover:shadow-md dark:border-gray-800">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <h3 class="mt-3 font-semibold text-gray-900 dark:text-white">Addresses</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your addresses</p>
            </a>

            <button wire:click="logout"
                    class="rounded-lg border border-gray-200 p-6 text-left transition-shadow hover:shadow-md dark:border-gray-800">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                <h3 class="mt-3 font-semibold text-gray-900 dark:text-white">Log out</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sign out of your account</p>
            </button>
        </div>

        {{-- Recent Orders --}}
        @if($recentOrders->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                            @foreach($recentOrders as $order)
                                <tr wire:key="order-{{ $order->id }}">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <x-storefront.badge :variant="match($order->status->value) {
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'fulfilled' => 'info',
                                            'cancelled' => 'default',
                                            'refunded' => 'sale',
                                            default => 'default',
                                        }">{{ ucfirst($order->status->value) }}</x-storefront.badge>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        <x-storefront.price :amount="$order->total_amount" :currency="$currency" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <a href="{{ route('storefront.account.orders.show', $order->order_number) }}"
                                           class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
