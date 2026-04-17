<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight">Order {{ $order->order_number }}</h1>
        <p class="text-sm text-neutral-600 dark:text-neutral-400">
            Placed on {{ optional($order->placed_at)->format('F j, Y') }} &middot; {{ $order->status->value }}
        </p>
    </header>

    <section class="flex flex-col gap-3 rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="text-lg font-semibold">Items</h2>
        <ul class="divide-y divide-neutral-200 dark:divide-neutral-800">
            @foreach ($order->lines as $line)
                <li wire:key="line-{{ $line->id }}" class="flex items-center justify-between gap-4 py-3 text-sm">
                    <div>
                        <p class="font-medium">{{ $line->title_snapshot }}</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Qty {{ $line->quantity }}</p>
                    </div>
                    <div class="font-semibold">
                        {{ number_format($line->total_amount / 100, 2) }} {{ $order->currency }}
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-neutral-200 bg-white p-5 text-sm dark:border-neutral-800 dark:bg-neutral-900">
            <h3 class="font-semibold">Totals</h3>
            <dl class="mt-3 flex flex-col gap-1">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ number_format($order->subtotal_amount / 100, 2) }} {{ $order->currency }}</dd></div>
                <div class="flex justify-between"><dt>Shipping</dt><dd>{{ number_format($order->shipping_amount / 100, 2) }} {{ $order->currency }}</dd></div>
                <div class="flex justify-between"><dt>Tax</dt><dd>{{ number_format($order->tax_amount / 100, 2) }} {{ $order->currency }}</dd></div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between"><dt>Discount</dt><dd>-{{ number_format($order->discount_amount / 100, 2) }} {{ $order->currency }}</dd></div>
                @endif
                <div class="mt-2 flex justify-between border-t border-neutral-200 pt-2 font-semibold dark:border-neutral-800"><dt>Total</dt><dd>{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</dd></div>
            </dl>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-5 text-sm dark:border-neutral-800 dark:bg-neutral-900">
            <h3 class="font-semibold">Shipping address</h3>
            @php $shipping = $order->shipping_address_json ?? []; @endphp
            <address class="mt-3 not-italic text-neutral-700 dark:text-neutral-300">
                {{ ($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '') }}<br>
                {{ $shipping['address1'] ?? '' }}<br>
                {{ $shipping['city'] ?? '' }} {{ $shipping['postal_code'] ?? '' }}<br>
                {{ $shipping['country_code'] ?? '' }}
            </address>
        </div>
    </section>

    <a href="{{ url('/account/orders') }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Back to orders</a>
</div>
