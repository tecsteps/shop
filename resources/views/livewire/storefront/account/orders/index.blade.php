<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
        ['label' => 'Orders'],
    ]" class="mb-4" />

    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Order history</h1>

    @if ($orders->isEmpty())
        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">You haven't placed any orders yet.</p>
    @else
        {{-- Table on desktop (spec 04 §10.4) --}}
        <table class="mt-8 hidden w-full sm:table">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <th scope="col" class="pb-3 pr-4">Order</th>
                    <th scope="col" class="pb-3 pr-4">Date</th>
                    <th scope="col" class="pb-3 pr-4">Status</th>
                    <th scope="col" class="pb-3 pr-4">Total</th>
                    <th scope="col" class="pb-3"><span class="sr-only">View</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($orders as $order)
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="py-4 pr-4">
                            <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                               class="text-sm font-medium text-blue-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="py-4 pr-4 text-sm text-gray-600 dark:text-gray-300">{{ $order->placed_at?->format('M j, Y') }}</td>
                        <td class="py-4 pr-4">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-storefront::order-status-badge :status="$order->status->value" />
                                @if ($order->financial_status !== $order->status)
                                    <x-storefront::order-status-badge :status="$order->financial_status->value" />
                                @endif
                                <x-storefront::order-status-badge :status="$order->fulfillment_status->value" />
                            </div>
                        </td>
                        <td class="py-4 pr-4 text-sm text-gray-600 dark:text-gray-300">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                        <td class="py-4 text-right">
                            <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                               class="text-sm font-medium text-blue-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Cards on mobile (spec 04 §10.4) --}}
        <div class="mt-6 space-y-4 sm:hidden">
            @foreach ($orders as $order)
                <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                   wire:key="order-card-{{ $order->id }}"
                   class="block rounded-xl border border-gray-200 p-4 transition hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $order->order_number }}</span>
                        <x-storefront::order-status-badge :status="$order->status->value" />
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $order->placed_at?->format('M j, Y') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @endif
</div>
