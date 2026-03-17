<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    {{-- Success Header --}}
    <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Thank you for your order!</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Order #{{ $checkoutId }}</p>
        @if($email)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">We've sent a confirmation to {{ $email }}</p>
        @endif
    </div>

    {{-- Order Items --}}
    @if(count($lines) > 0)
        <div class="mt-8 rounded-lg border border-gray-200 p-6 dark:border-gray-800">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Order Summary</h2>
            <div class="mt-4 space-y-3">
                @foreach($lines as $line)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $line['product_title'] }}</span>
                            @if($line['variant_title'])
                                <span class="text-gray-500 dark:text-gray-400"> - {{ $line['variant_title'] }}</span>
                            @endif
                            <span class="text-gray-500 dark:text-gray-400"> x {{ $line['quantity'] }}</span>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($line['line_total_amount'] / 100, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Shipping Address & Payment Method --}}
    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        @if(!empty($shippingAddress))
            <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Shipping Address</h3>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}</p>
                    <p>{{ $shippingAddress['address1'] ?? '' }}</p>
                    @if(!empty($shippingAddress['address2']))
                        <p>{{ $shippingAddress['address2'] }}</p>
                    @endif
                    <p>{{ $shippingAddress['postal_code'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}</p>
                    <p>{{ $shippingAddress['country'] ?? '' }}</p>
                </div>
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Payment Method</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                @switch($paymentMethod)
                    @case('credit_card')
                        Credit Card
                        @break
                    @case('paypal')
                        PayPal
                        @break
                    @case('bank_transfer')
                        Bank Transfer
                        @break
                    @default
                        {{ $paymentMethod }}
                @endswitch
            </p>
        </div>
    </div>

    {{-- Bank Transfer Instructions --}}
    @if($paymentMethod === 'bank_transfer' && !empty($totals))
        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-900/20">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300">Bank Transfer Instructions</h4>
                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-400">Please transfer the total amount to the following account:</p>
                    <dl class="mt-3 space-y-1 text-sm text-blue-800 dark:text-blue-400">
                        <div class="flex gap-2">
                            <dt class="font-medium">Bank:</dt>
                            <dd>Mock Bank AG</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium">IBAN:</dt>
                            <dd>DE89 3704 0044 0532 0130 00</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium">BIC:</dt>
                            <dd>COBADEFFXXX</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium">Amount:</dt>
                            <dd>{{ number_format(($totals['total'] ?? 0) / 100, 2) }} EUR</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium">Reference:</dt>
                            <dd>#{{ $checkoutId }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-sm text-blue-700 dark:text-blue-400">Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Totals --}}
    @if(!empty($totals))
        <div class="mt-6 rounded-lg border border-gray-200 p-6 dark:border-gray-800">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                    <span class="text-gray-900 dark:text-white">{{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span>
                </div>
                @if(($totals['discount'] ?? 0) > 0)
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span>Discount</span>
                        <span>-{{ number_format($totals['discount'] / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                    <span class="text-gray-900 dark:text-white">{{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}</span>
                </div>
                @if(($totals['tax_total'] ?? 0) > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tax</span>
                        <span class="text-gray-900 dark:text-white">{{ number_format($totals['tax_total'] / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-700">
                    <span class="text-gray-900 dark:text-white">Total</span>
                    <span class="text-gray-900 dark:text-white">{{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="mt-8 flex items-center justify-center gap-4">
        <a href="{{ route('home') }}"
           class="rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
            Continue Shopping
        </a>
    </div>
</div>
