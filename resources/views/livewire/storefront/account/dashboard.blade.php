<div class="space-y-10">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Account</p>
            <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Hi, {{ $customer->name }}</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</p>
        </div>
        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">
                Sign out
            </button>
        </form>
    </header>

    <nav class="grid gap-4 sm:grid-cols-3" aria-label="Account sections">
        <a href="{{ route('storefront.account.orders.index') }}" class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Orders</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review your order history.</p>
        </a>
        <a href="{{ route('storefront.account.addresses.index') }}" class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Addresses</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage saved shipping addresses.</p>
        </a>
        <a href="{{ route('storefront.home') }}" class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Continue shopping</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Browse the latest arrivals.</p>
        </a>
    </nav>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recent orders</h2>

        @if ($recentOrders->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">You have not placed any orders yet.</p>
            </div>
        @else
            <ul class="divide-y divide-zinc-200 overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @foreach ($recentOrders as $order)
                    <li class="flex items-center justify-between gap-4 p-5">
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $order->order_number }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                            </span>
                            <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" class="text-xs font-medium text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100">
                                View
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
