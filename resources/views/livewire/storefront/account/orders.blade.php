<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-10">
            <aside class="hidden lg:block">
                <div class="sticky top-24">
                    @include('storefront.partials.account-nav')
                </div>
            </aside>

            <div>
                <div class="lg:hidden">
                    @include('storefront.partials.account-nav')
                </div>

                <h1 class="mt-6 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl lg:mt-0 dark:text-white">Order History</h1>

                @if ($this->orders->isEmpty())
                    <div class="mt-8 rounded-2xl border border-zinc-200 p-10 text-center dark:border-zinc-800">
                        <p class="text-base font-semibold text-zinc-900 dark:text-white">No orders yet</p>
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">When you place an order it will appear here.</p>
                        <a href="{{ route('storefront.collections.index') }}" class="mt-6 inline-flex rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">
                            Start shopping
                        </a>
                    </div>
                @else
                    {{-- Desktop table --}}
                    <div class="mt-6 hidden overflow-hidden rounded-2xl border border-zinc-200 sm:block dark:border-zinc-800">
                        <table class="w-full text-left">
                            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                                    <th scope="col" class="px-5 py-3.5"><span class="sr-only">View</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @foreach ($this->orders as $order)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-sm font-medium text-blue-600 transition hover:underline dark:text-blue-400">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ $order->placed_at?->format('M j, Y') }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <x-storefront-badge :text="ucfirst($order->status)" :variant="$this->statusBadgeVariant($order->status)" />
                                        </td>
                                        <td class="px-5 py-4">
                                            <x-storefront-price :amount="$order->total_amount" :currency="$order->currency" class="text-sm" />
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-sm font-medium text-blue-600 transition hover:underline dark:text-blue-400">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile cards --}}
                    <ul class="mt-6 space-y-3 sm:hidden">
                        @foreach ($this->orders as $order)
                            <li class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div class="flex items-center justify-between gap-3">
                                    <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-sm font-semibold text-blue-600 transition hover:underline dark:text-blue-400">
                                        {{ $order->order_number }}
                                    </a>
                                    <x-storefront-badge :text="ucfirst($order->status)" :variant="$this->statusBadgeVariant($order->status)" />
                                </div>
                                <div class="mt-2 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
                                    <span>{{ $order->placed_at?->format('M j, Y') }}</span>
                                    <x-storefront-price :amount="$order->total_amount" :currency="$order->currency" class="text-sm" />
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <x-storefront-pagination :paginator="$this->orders" />
                @endif
            </div>
        </div>
    </div>
</div>
