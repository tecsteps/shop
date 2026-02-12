<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order History</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View and track your orders.</p>
        </div>
        <a href="{{ route('storefront.account.dashboard') }}" wire:navigate
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            &larr; Back to account
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">You have not placed any orders yet.</p>
            <a href="{{ route('storefront.home') }}" wire:navigate class="mt-4 inline-block text-blue-600 dark:text-blue-400 hover:underline">
                Start shopping
            </a>
        </div>
    @else
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Order</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($orders as $order)
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
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('storefront.account.orders.show', $order->order_number) }}" wire:navigate
                                   class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    View
                                </a>
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
