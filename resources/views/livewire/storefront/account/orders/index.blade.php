<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Order History') }}</h1>

    <x-storefront.account-nav current="orders" class="mt-6" />

    @if ($orders->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="size-20 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __("You haven't placed any orders yet.") }}</p>
            <a
                href="{{ route('home') }}"
                class="mt-6 rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                {{ __('Start shopping') }}
            </a>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="mt-8 hidden overflow-hidden rounded-2xl border border-zinc-200 md:block dark:border-zinc-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Order') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Date') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Fulfillment') }}</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Total') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Action') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($orders as $order)
                        <tr wire:key="order-row-{{ $order->getKey() }}">
                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                                    class="rounded font-medium text-zinc-900 transition hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-white dark:hover:text-blue-400"
                                >
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3"><x-storefront.order-status-badge :status="$order->status" /></td>
                            <td class="px-4 py-3"><x-storefront.order-status-badge :status="$order->fulfillment_status" /></td>
                            <td class="px-4 py-3 text-right"><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="text-sm" /></td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                                    class="rounded text-sm font-medium text-blue-600 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400"
                                >
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <ul class="mt-8 space-y-4 md:hidden" role="list">
            @foreach ($orders as $order)
                <li wire:key="order-card-{{ $order->getKey() }}">
                    <a
                        href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                        class="block rounded-2xl border border-zinc-200 p-4 transition hover:border-zinc-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-800 dark:hover:border-zinc-700"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-semibold text-zinc-900 dark:text-white">{{ $order->order_number }}</span>
                            <x-storefront.order-status-badge :status="$order->status" />
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>{{ $order->placed_at?->format('M j, Y') }}</span>
                            <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="text-sm" />
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <x-storefront.pagination :paginator="$orders" class="mt-8" />
    @endif
</div>
