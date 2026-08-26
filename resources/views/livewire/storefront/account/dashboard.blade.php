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

                <h1 class="mt-6 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl lg:mt-0 dark:text-white">
                    Welcome back, {{ $this->customer->name }}!
                </h1>

                {{-- Quick links --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <a href="{{ route('account.orders.index') }}" class="group rounded-2xl border border-zinc-200 p-5 transition hover:shadow-md dark:border-zinc-800">
                        <svg class="size-6 text-zinc-400 transition group-hover:text-zinc-700 dark:group-hover:text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 18h10" /><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">Order history</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">View all your orders</p>
                    </a>

                    <a href="{{ route('account.addresses.index') }}" class="group rounded-2xl border border-zinc-200 p-5 transition hover:shadow-md dark:border-zinc-800">
                        <svg class="size-6 text-zinc-400 transition group-hover:text-zinc-700 dark:group-hover:text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /><circle cx="12" cy="10" r="3" />
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">Addresses</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Manage your addresses</p>
                    </a>

                    <form method="POST" action="{{ route('account.logout') }}" class="group rounded-2xl border border-zinc-200 p-5 transition hover:shadow-md dark:border-zinc-800">
                        @csrf
                        <button type="submit" class="w-full text-left">
                            <svg class="size-6 text-zinc-400 transition group-hover:text-red-600 dark:group-hover:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><path d="m16 17 5-5-5-5" /><path d="M21 12H9" />
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">Log out</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sign out of your account</p>
                        </button>
                    </form>
                </div>

                {{-- Recent orders --}}
                <section class="mt-10" aria-labelledby="recent-orders-heading">
                    <h2 id="recent-orders-heading" class="text-lg font-semibold text-zinc-900 dark:text-white">Recent orders</h2>

                    @if ($this->recentOrders->isEmpty())
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">You have not placed any orders yet.</p>
                    @else
                        <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                            <table class="w-full text-left">
                                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                                        <th scope="col" class="hidden px-5 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 sm:table-cell dark:text-zinc-400">Date</th>
                                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                                        <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                                        <th scope="col" class="px-5 py-3"><span class="sr-only">View</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                    @foreach ($this->recentOrders as $order)
                                        <tr>
                                            <td class="px-5 py-4">
                                                <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-sm font-medium text-blue-600 transition hover:underline dark:text-blue-400">
                                                    {{ $order->order_number }}
                                                </a>
                                            </td>
                                            <td class="hidden px-5 py-4 text-sm text-zinc-600 sm:table-cell dark:text-zinc-300">
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
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
