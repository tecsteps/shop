<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'My Account'],
    ]" class="mb-8" />

    <div class="lg:grid lg:grid-cols-12 lg:gap-x-8">
        {{-- Sidebar --}}
        <aside class="lg:col-span-3 mb-8 lg:mb-0">
            @include('livewire.storefront.account.partials.sidebar')
        </aside>

        {{-- Main Content --}}
        <div class="lg:col-span-9">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Dashboard</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Welcome back, {{ $customer->name }}! ({{ $customer->email }})
            </p>

            {{-- Recent Orders --}}
            <div class="mt-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>
                    @if($recentOrders->isNotEmpty())
                        <a href="{{ route('customer.orders') }}"
                           wire:navigate
                           class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            View all orders &rarr;
                        </a>
                    @endif
                </div>

                @if($recentOrders->isEmpty())
                    <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                        <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">You haven't placed any orders yet.</p>
                        <a href="{{ route('storefront.home') }}"
                           wire:navigate
                           class="mt-4 inline-flex items-center text-sm font-medium text-zinc-900 hover:text-zinc-700 dark:text-white dark:hover:text-zinc-300">
                            Start shopping &rarr;
                        </a>
                    </div>
                @else
                    <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ $order->order_number }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $order->placed_at?->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                                            @include('livewire.storefront.account.partials.order-status-badge', ['status' => $order->status])
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-900 dark:text-white">
                                            <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <a href="{{ route('customer.orders.show', $order->order_number) }}"
                                               wire:navigate
                                               class="font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
