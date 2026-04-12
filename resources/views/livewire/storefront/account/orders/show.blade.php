<div class="space-y-8">
    <header class="space-y-2">
        <a href="{{ route('storefront.account.orders.index') }}" class="text-xs text-zinc-500 underline-offset-2 hover:underline dark:text-zinc-400">Back to orders</a>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Order {{ $order->order_number }}</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Placed on {{ $order->placed_at?->format('F j, Y') }}</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Items</h2>
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($order->lines as $line)
                    <li class="flex items-center justify-between gap-4 py-4">
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 rounded-lg bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900"></div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $line->title_snapshot }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty {{ $line->quantity }}</p>
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                        </p>
                    </li>
                @endforeach
            </ul>

            <div class="space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
                <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Subtotal</span>
                    <span class="text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" /></span>
                </div>
                <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Shipping</span>
                    <span class="text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" /></span>
                </div>
                <div class="flex items-center justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-zinc-100">
                    <span>Total</span>
                    <span><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></span>
                </div>
            </div>
        </section>

        <aside class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Status</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Payment: {{ $order->financial_status?->value ?? 'pending' }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Fulfillment: {{ $order->fulfillment_status?->value ?? 'unfulfilled' }}</p>
            </div>

            @php($shipping = $order->shipping_address_json ?? [])
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Shipping address</h3>
                <address class="not-italic mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}<br />
                    @if (! empty($shipping['address1'])) {{ $shipping['address1'] }}<br /> @endif
                    @if (! empty($shipping['city'])) {{ $shipping['city'] }} {{ $shipping['postal_code'] ?? '' }}<br /> @endif
                    {{ $shipping['country'] ?? '' }}
                </address>
            </div>
        </aside>
    </div>
</div>
