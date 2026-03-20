<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Order History') }}</h1>

    {{-- Navigation --}}
    <nav class="mt-4 flex gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
        <a href="{{ route('customer.account') }}"
           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ __('Dashboard') }}
        </a>
        <a href="{{ route('customer.orders') }}"
           class="text-sm font-medium text-blue-600 dark:text-blue-400">
            {{ __('Orders') }}
        </a>
        <a href="{{ route('customer.addresses') }}"
           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ __('Addresses') }}
        </a>
    </nav>

    @if($orders->isEmpty())
        <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">{{ __('You have no orders yet.') }}</p>
    @else
        <div class="mt-8 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Order') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Payment') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Fulfillment') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <a href="{{ route('customer.orders.show', $order->order_number) }}"
                                   class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->placed_at?->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @if($order->financial_status->value === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($order->financial_status->value === 'refunded') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @if($order->fulfillment_status->value === 'fulfilled') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($order->fulfillment_status->value === 'partial') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif
                                ">
                                    {{ ucfirst($order->fulfillment_status->value) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                ${{ number_format($order->total_amount / 100, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
