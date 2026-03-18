<div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
    @if($order)
        <div class="text-center">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white">Thank you for your order!</h1>
            <p class="mt-2 text-lg text-zinc-600 dark:text-zinc-400">Order {{ $order->order_number }}</p>
        </div>

        {{-- Payment status --}}
        <div class="mt-8 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            @if($order->financial_status->value === 'paid')
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>
                    <span class="text-sm font-medium text-green-700 dark:text-green-400">Payment confirmed</span>
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Paid via {{ $order->payment_method->value === 'credit_card' ? 'Credit Card' : ($order->payment_method->value === 'paypal' ? 'PayPal' : 'Bank Transfer') }}
                </p>
            @else
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                    <span class="text-sm font-medium text-orange-700 dark:text-orange-400">Awaiting payment</span>
                </div>
                <div class="mt-3 rounded-lg bg-orange-50 p-4 dark:bg-orange-900/20">
                    <p class="text-sm font-medium text-orange-800 dark:text-orange-300">Bank Transfer Instructions</p>
                    <div class="mt-2 space-y-1 text-sm text-orange-700 dark:text-orange-400">
                        <p>Bank: Acme Bank AG</p>
                        <p>IBAN: DE89 3704 0044 0532 0130 00</p>
                        <p>BIC: COBADEFFXXX</p>
                        <p>Reference: {{ $order->order_number }}</p>
                        <p class="mt-2 font-medium">Amount: {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</p>
                    </div>
                    <p class="mt-3 text-xs text-orange-600 dark:text-orange-500">Please complete your transfer within 7 days. Your order will be processed once payment is received.</p>
                </div>
            @endif
        </div>

        {{-- Order items --}}
        <div class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Items</h2>
            <div class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach($order->lines as $line)
                    <div class="flex justify-between py-3 text-sm">
                        <div>
                            <p class="text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                            @if($line->variant_title_snapshot)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant_title_snapshot }}</p>
                            @endif
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty: {{ $line->quantity }}</p>
                        </div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ number_format($line->total_amount / 100, 2) }} {{ $order->currency }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Shipping address --}}
        @if($order->shipping_address_json)
            <div class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Shipping Address</h2>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <p>{{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['address1'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['postal_code'] ?? '' }} {{ $order->shipping_address_json['city'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['country'] ?? '' }}</p>
                </div>
            </div>
        @endif

        {{-- Totals --}}
        <div class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Subtotal</span>
                    <span>{{ number_format($order->subtotal_amount / 100, 2) }} {{ $order->currency }}</span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ number_format($order->discount_amount / 100, 2) }} {{ $order->currency }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Shipping</span>
                    <span>{{ number_format($order->shipping_amount / 100, 2) }} {{ $order->currency }}</span>
                </div>
                @if($order->tax_amount > 0)
                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Tax</span>
                        <span>{{ number_format($order->tax_amount / 100, 2) }} {{ $order->currency }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">
                    <span>Total</span>
                    <span>{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</span>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('home') }}"
               class="inline-block rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                Continue Shopping
            </a>
        </div>
    @else
        <div class="text-center">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order not found</h1>
            <a href="{{ route('home') }}"
               class="mt-8 inline-block rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                Continue Shopping
            </a>
        </div>
    @endif
</div>
