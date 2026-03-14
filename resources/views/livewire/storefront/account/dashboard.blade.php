<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Account</h1>
        <form method="POST" action="{{ route('customer.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" size="sm">Sign out</flux:button>
        </form>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-8">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Welcome, {{ $customerName }}</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $customerEmail }}</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        <a href="{{ route('customer.orders') }}" class="block bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 hover:border-zinc-400 dark:hover:border-zinc-500 transition" wire:navigate>
            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Orders</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">View your order history</p>
        </a>
        <a href="{{ route('customer.addresses') }}" class="block bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 hover:border-zinc-400 dark:hover:border-zinc-500 transition" wire:navigate>
            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Addresses</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage your saved addresses</p>
        </a>
    </div>

    @if ($recentOrders->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>
                <a href="{{ route('customer.orders') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-2 font-medium text-zinc-500 dark:text-zinc-400">Order</th>
                            <th class="text-left py-2 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="text-left py-2 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-right py-2 font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr wire:key="recent-order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-700/50">
                                <td class="py-3">
                                    <a href="{{ route('customer.orders.show', $order->order_number) }}" class="text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                                <td class="py-3">
                                    <x-storefront.badge :variant="match($order->status->value) { 'paid' => 'new', 'fulfilled' => 'new', 'cancelled' => 'sold-out', 'refunded' => 'sold-out', default => 'draft' }">
                                        {{ ucfirst($order->status->value) }}
                                    </x-storefront.badge>
                                </td>
                                <td class="py-3 text-right">
                                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
