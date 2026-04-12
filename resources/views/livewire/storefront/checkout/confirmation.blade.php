<div class="space-y-10">
    <header class="space-y-3 text-center">
        <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><polyline points="20 6 9 17 4 12" /></svg>
        </div>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Thank you for your order</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Order {{ $order->order_number }} is confirmed. A receipt has been sent to {{ $order->email }}.</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Order details</h2>
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
                @if ($order->tax_amount > 0)
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Tax</span>
                        <span class="text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" /></span>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-zinc-100">
                    <span>Total</span>
                    <span><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></span>
                </div>
            </div>
        </section>

        <aside class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Shipping address</h3>
                @php($shipping = $order->shipping_address_json ?? [])
                <address class="not-italic mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}<br />
                    @if (! empty($shipping['address1']))
                        {{ $shipping['address1'] }}<br />
                    @endif
                    @if (! empty($shipping['city']))
                        {{ $shipping['city'] }} {{ $shipping['postal_code'] ?? '' }}<br />
                    @endif
                    {{ $shipping['country'] ?? '' }}
                </address>
            </div>

            <div class="space-y-1 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Status</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Payment: {{ $order->financial_status?->value ?? 'pending' }}
                </p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Fulfillment: {{ $order->fulfillment_status?->value ?? 'unfulfilled' }}
                </p>
            </div>

            <a href="{{ route('storefront.home') }}" class="inline-flex w-full items-center justify-center rounded-full border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">
                Continue shopping
            </a>
        </aside>
    </div>
</div>
