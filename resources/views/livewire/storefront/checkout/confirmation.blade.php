<div class="mx-auto max-w-3xl px-4 py-12 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
        <flux:icon.check class="size-8 text-green-600 dark:text-green-400" variant="outline" />
    </div>
    <h1 class="mt-4 text-3xl font-bold">Thank you for your order!</h1>
    <p class="mt-2 text-lg text-zinc-600 dark:text-zinc-400">Order {{ $order->order_number }}</p>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">We've sent a confirmation to {{ $order->email }}</p>

    <div class="mt-8 rounded-lg border border-zinc-200 p-6 text-left dark:border-zinc-800">
        <h2 class="font-semibold">Order Summary</h2>
        <ul class="mt-4 flex flex-col gap-3">
            @foreach ($order->orderLines as $line)
                <li wire:key="confirm-line-{{ $line->getKey() }}" class="flex items-center justify-between gap-4 text-sm">
                    <span>{{ $line->title_snapshot }} x{{ $line->quantity }}</span>
                    <span class="font-semibold">{{ \App\Support\Money::format($line->total_amount, $order->currency) }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <div>
                <h3 class="text-sm font-semibold">Shipping Address</h3>
                @php $a = (array) $order->shipping_address_json; @endphp
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}<br />
                    {{ $a['address1'] ?? '' }}<br />
                    {{ $a['city'] ?? '' }} {{ $a['postal_code'] ?? '' }}<br />
                    {{ $a['country'] ?? $a['country_code'] ?? '' }}
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold">Payment Method</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $paymentLabel }}</p>
            </div>
        </div>

        @if ($bankInstructions)
            <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-left dark:border-blue-900 dark:bg-blue-950/40" role="note" aria-label="Bank Transfer Instructions">
                <p class="font-semibold">Bank Transfer Instructions</p>
                <p class="mt-1 text-sm">Please transfer the total amount to the following account:</p>
                <dl class="mt-2 grid grid-cols-2 gap-1 text-sm">
                    <dt>Bank:</dt><dd>{{ $bankInstructions['bank_name'] }}</dd>
                    <dt>IBAN:</dt><dd>{{ $bankInstructions['iban'] }}</dd>
                    <dt>BIC:</dt><dd>{{ $bankInstructions['bic'] }}</dd>
                    <dt>Amount:</dt><dd>{{ $bankInstructions['amount_formatted'] }}</dd>
                    <dt>Reference:</dt><dd>{{ $bankInstructions['reference'] }}</dd>
                </dl>
                <p class="mt-2 text-sm">Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.</p>
            </div>
        @endif

        <dl class="mt-6 flex flex-col gap-1 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ \App\Support\Money::format($order->subtotal_amount, $order->currency) }}</dd></div>
            <div class="flex justify-between"><dt>Shipping</dt><dd>{{ \App\Support\Money::format($order->shipping_amount, $order->currency) }}</dd></div>
            <div class="flex justify-between"><dt>Tax</dt><dd>{{ \App\Support\Money::format($order->tax_amount, $order->currency) }}</dd></div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-green-700 dark:text-green-400"><dt>Discount</dt><dd>-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</dd></div>
            @endif
            <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-700"><dt>Total</dt><dd>{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</dd></div>
        </dl>
    </div>

    <div class="mt-6 flex items-center justify-center gap-4">
        <a href="{{ route('home') }}" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">Continue shopping</a>
        @if ($ownsOrder)
            <a href="{{ route('customer.orders.show', ['orderNumber' => urlencode($order->order_number)]) }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">View order</a>
        @endif
    </div>
</div>
