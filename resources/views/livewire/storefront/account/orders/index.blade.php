<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.dashboard') }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" wire:navigate>
            <flux:icon name="arrow-left" class="size-5" />
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order History</h1>
    </div>

    @if ($orders->isEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <p class="text-zinc-500 dark:text-zinc-400 mb-4">You have no orders yet.</p>
            <flux:button href="{{ route('storefront.home') }}" variant="primary" wire:navigate>
                Start Shopping
            </flux:button>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Order</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-right px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-700/50 last:border-0">
                                <td class="px-6 py-4">
                                    <a href="{{ route('customer.orders.show', $order->order_number) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" wire:navigate>
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <x-storefront.badge :variant="match($order->status->value) { 'paid' => 'new', 'fulfilled' => 'new', 'cancelled' => 'sold-out', 'refunded' => 'sold-out', default => 'draft' }">
                                        {{ ucfirst($order->status->value) }}
                                    </x-storefront.badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('customer.orders.show', $order->order_number) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
