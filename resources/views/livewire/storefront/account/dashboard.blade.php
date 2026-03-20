<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Account</h1>
        <flux:button wire:click="logout" variant="ghost" size="sm">
            {{ __('Log out') }}
        </flux:button>
    </div>

    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Welcome back, :name', ['name' => $customer->name]) }}
    </p>

    {{-- Navigation --}}
    <nav class="mt-8 flex gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
        <a href="{{ route('customer.account') }}"
           class="text-sm font-medium text-blue-600 dark:text-blue-400">
            {{ __('Dashboard') }}
        </a>
        <a href="{{ route('customer.orders') }}"
           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ __('Orders') }}
        </a>
        <a href="{{ route('customer.addresses') }}"
           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ __('Addresses') }}
        </a>
    </nav>

    {{-- Recent orders --}}
    <section class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent Orders') }}</h2>

        @if($recentOrders->isEmpty())
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('You have no orders yet.') }}</p>
        @else
            <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Order') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @foreach($recentOrders as $order)
                            <tr>
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
                                        @switch($order->status->value)
                                            @case('paid') bg-green-100 text-green-800 dark:bg-green-900 text-green-200 @break
                                            @case('fulfilled') bg-blue-100 text-blue-800 dark:bg-blue-900 text-blue-200 @break
                                            @case('cancelled') bg-red-100 text-red-800 dark:bg-red-900 text-red-200 @break
                                            @case('refunded') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 text-yellow-200 @break
                                            @default bg-gray-100 text-gray-800 dark:bg-gray-700 text-gray-200
                                        @endswitch
                                    ">
                                        {{ ucfirst($order->status->value) }}
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
                <a href="{{ route('customer.orders') }}"
                   class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ __('View all orders') }} &rarr;
                </a>
            </div>
        @endif
    </section>
</div>
