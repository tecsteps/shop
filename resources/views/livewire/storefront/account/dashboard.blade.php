<div class="max-w-4xl mx-auto px-4 py-12">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Welcome, {{ $customer->first_name }}
    </h1>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Manage your account and view your orders.</p>

    {{-- Quick links --}}
    <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('storefront.account.orders') }}" wire:navigate
           class="block p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">Orders</span>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">View your order history</p>
        </a>

        <a href="{{ route('storefront.account.addresses') }}" wire:navigate
           class="block p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">Addresses</span>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Manage your addresses</p>
        </a>

        <form action="{{ route('storefront.account.logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="block w-full text-left p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-red-300 dark:hover:border-red-700 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="font-medium text-gray-900 dark:text-white">Log out</span>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sign out of your account</p>
            </button>
        </form>
    </div>

    {{-- Recent orders --}}
    @if($recentOrders->isNotEmpty())
        <div class="mt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                <a href="{{ route('storefront.account.orders') }}" wire:navigate class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    View all
                </a>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Order</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recentOrders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('storefront.account.orders.show', $order->order_number) }}" wire:navigate
                                       class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $order->placed_at?->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @include('livewire.storefront.account.orders._status-badge', ['status' => $order->status])
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                    {{ \App\Support\MoneyFormatter::format($order->total_amount, $order->currency ?? 'EUR') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
