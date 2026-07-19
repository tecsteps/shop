@php
    $order = $this->order;
    $address = $order->shipping_address_json ?? [];
    $payment = $order->payments->first();
@endphp

<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    {{-- Success header (spec 04 §9.1) --}}
    <div class="text-center">
        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
            <svg class="size-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <h1 class="mt-6 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Thank you for your order!</h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Order {{ $order->order_number }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">We've sent a confirmation to {{ $order->email }}</p>
    </div>

    {{-- Items (spec 04 §9.2) --}}
    <section aria-labelledby="confirmation-items" class="mt-10 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <h2 id="confirmation-items" class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-white">Order Summary</h2>
        <ul class="divide-y divide-gray-200 dark:divide-gray-800">
            @foreach ($order->lines as $line)
                <li class="flex items-center gap-4 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $line->title_snapshot }}</p>
                        @if ($line->sku_snapshot)
                            <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $line->sku_snapshot }}</p>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">×{{ $line->quantity }}</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($line->total_amount, $order->currency) }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Address & payment (spec 04 §9.2) --}}
    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        <section aria-labelledby="confirmation-address" class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-950">
            <h2 id="confirmation-address" class="text-sm font-semibold text-gray-900 dark:text-white">Shipping Address</h2>
            <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-400">
                {{ $address['first_name'] ?? '' }} {{ $address['last_name'] ?? '' }}<br>
                {{ $address['address1'] ?? '' }}<br>
                {{ $address['postal_code'] ?? '' }} {{ $address['city'] ?? '' }}<br>
                {{ $address['country_code'] ?? $address['country'] ?? '' }}
            </address>
        </section>
        <section aria-labelledby="confirmation-payment" class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-950">
            <h2 id="confirmation-payment" class="text-sm font-semibold text-gray-900 dark:text-white">Payment Method</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                @if ($order->payment_method->value === 'credit_card')
                    Credit Card @if ($paymentLast4) ending in {{ $paymentLast4 }} @endif
                @elseif ($order->payment_method->value === 'paypal')
                    PayPal
                @else
                    Bank Transfer
                @endif
            </p>
        </section>
    </div>

    {{-- Bank transfer instructions (spec 04 §9.2) --}}
    @if ($isBankTransfer)
        <section aria-labelledby="bank-transfer-instructions" class="mt-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-4 dark:border-blue-900 dark:bg-blue-950">
            <h2 id="bank-transfer-instructions" class="flex items-center gap-2 text-sm font-semibold text-blue-900 dark:text-blue-100">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12v-.008z" />
                </svg>
                Bank Transfer Instructions
            </h2>
            <p class="mt-2 text-sm text-blue-800 dark:text-blue-200">Please transfer the total amount to the following account:</p>
            <dl class="mt-3 space-y-1 text-sm text-blue-900 dark:text-blue-100">
                <div class="flex justify-between"><dt class="font-medium">Bank</dt><dd>Mock Bank AG</dd></div>
                <div class="flex justify-between"><dt class="font-medium">IBAN</dt><dd>DE89 3704 0044 0532 0130 00</dd></div>
                <div class="flex justify-between"><dt class="font-medium">BIC</dt><dd>COBADEFFXXX</dd></div>
                <div class="flex justify-between"><dt class="font-medium">Amount</dt><dd>{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</dd></div>
                <div class="flex justify-between"><dt class="font-medium">Reference</dt><dd>{{ $order->order_number }}</dd></div>
            </dl>
            <p class="mt-3 text-sm text-blue-800 dark:text-blue-200">Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.</p>
        </section>
    @endif

    {{-- Totals --}}
    <dl class="mt-6 space-y-2 rounded-lg border border-gray-200 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="flex items-center justify-between text-sm">
            <dt class="text-gray-600 dark:text-gray-400">Subtotal</dt>
            <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($order->subtotal_amount, $order->currency) }}</dd>
        </div>
        @if ($order->discount_amount > 0)
            <div class="flex items-center justify-between text-sm text-green-600 dark:text-green-400">
                <dt>Discount</dt>
                <dd>-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</dd>
            </div>
        @endif
        <div class="flex items-center justify-between text-sm">
            <dt class="text-gray-600 dark:text-gray-400">Shipping</dt>
            <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($order->shipping_amount, $order->currency) }}</dd>
        </div>
        <div class="flex items-center justify-between text-sm">
            <dt class="text-gray-600 dark:text-gray-400">Tax</dt>
            <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($order->tax_amount, $order->currency) }}</dd>
        </div>
        <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-800">
            <dt class="text-gray-900 dark:text-white">Total</dt>
            <dd class="text-gray-900 dark:text-white">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</dd>
        </div>
    </dl>

    {{-- Actions (spec 04 §9.3) --}}
    <div class="mt-8 flex items-center justify-center gap-4">
        <a href="{{ route('storefront.home') }}"
           class="rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
            Continue shopping
        </a>
        <a href="{{ $this->orderStatusUrl() }}"
           class="rounded-md border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
            View order status
        </a>
    </div>
</div>
