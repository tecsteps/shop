@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
@endphp

<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
            ['label' => 'Orders'],
        ]" />

        <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Order History</h1>

        @if($orders && $orders->isNotEmpty())
            {{-- Desktop Table --}}
            <div class="mt-6 hidden overflow-hidden rounded-lg border border-gray-200 sm:block dark:border-gray-800">
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
                        @foreach($orders as $order)
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

            {{-- Mobile Cards --}}
            <div class="mt-6 space-y-4 sm:hidden">
                @foreach($orders as $order)
                    <a href="{{ route('storefront.account.orders.show', $order->order_number) }}"
                       wire:key="order-card-{{ $order->id }}"
                       class="block rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</span>
                            <x-storefront.badge :variant="match($order->status->value) {
                                'pending' => 'warning',
                                'paid' => 'success',
                                'fulfilled' => 'info',
                                'cancelled' => 'default',
                                'refunded' => 'sale',
                                default => 'default',
                            }">{{ ucfirst($order->status->value) }}</x-storefront.badge>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $order->placed_at?->format('M d, Y') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                <x-storefront.price :amount="$order->total_amount" :currency="$currency" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($orders->hasPages())
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <div class="mt-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No orders yet</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Your orders will appear here once you make a purchase.</p>
                <a href="{{ route('home') }}" class="mt-4 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">
                    Start shopping
                </a>
            </div>
        @endif
    </div>
</div>
