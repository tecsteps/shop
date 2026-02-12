<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
    {{-- Success Header --}}
    <div class="text-center mb-10">
        <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">Thank you for your order!</h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Order #{{ $order->order_number }}</p>
        @if($order->email)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">We have sent a confirmation to {{ $order->email }}</p>
        @endif
    </div>

    {{-- Bank Transfer Instructions --}}
    @if($order->payment_method?->value === 'bank_transfer')
        <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Bank Transfer Instructions
            </h3>
            <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">Please transfer the total amount to the following account:</p>
            <dl class="text-sm space-y-1">
                <div class="flex gap-2"><dt class="font-medium text-blue-800 dark:text-blue-300 w-20">Bank:</dt><dd class="text-blue-700 dark:text-blue-400">Mock Bank AG</dd></div>
                <div class="flex gap-2"><dt class="font-medium text-blue-800 dark:text-blue-300 w-20">IBAN:</dt><dd class="text-blue-700 dark:text-blue-400">DE89 3704 0044 0532 0130 00</dd></div>
                <div class="flex gap-2"><dt class="font-medium text-blue-800 dark:text-blue-300 w-20">BIC:</dt><dd class="text-blue-700 dark:text-blue-400">COBADEFFXXX</dd></div>
                <div class="flex gap-2"><dt class="font-medium text-blue-800 dark:text-blue-300 w-20">Amount:</dt><dd class="text-blue-700 dark:text-blue-400">{{ \App\Support\MoneyFormatter::format($order->total_amount, $order->currency ?? 'EUR') }}</dd></div>
                <div class="flex gap-2"><dt class="font-medium text-blue-800 dark:text-blue-300 w-20">Reference:</dt><dd class="text-blue-700 dark:text-blue-400">#{{ $order->order_number }}</dd></div>
            </dl>
            <p class="mt-3 text-sm text-blue-600 dark:text-blue-400">Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.</p>
        </div>
    @endif

    {{-- Order Items --}}
    <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden mb-8">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Order Summary</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @foreach($order->lines as $line)
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $line->title_snapshot }}</p>
                        @if($line->variant_title_snapshot)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $line->variant_title_snapshot }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">x{{ $line->quantity }}</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($line->total_amount, $order->currency ?? 'EUR') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Address + Payment --}}
    <div class="grid sm:grid-cols-2 gap-6 mb-8">
        @if($order->shipping_address_json)
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Shipping Address</h3>
                @php $addr = $order->shipping_address_json; @endphp
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-0.5">
                    <p>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                    <p>{{ $addr['address1'] ?? '' }}</p>
                    @if(!empty($addr['address2'])) <p>{{ $addr['address2'] }}</p> @endif
                    <p>{{ $addr['city'] ?? '' }} {{ $addr['zip'] ?? '' }}</p>
                    <p>{{ $addr['country'] ?? '' }}</p>
                </div>
            </div>
        @endif
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Payment Method</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @php
                    $paymentLabel = match($order->payment_method?->value ?? '') {
                        'credit_card' => 'Credit Card',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        default => 'N/A',
                    };
                @endphp
                {{ $paymentLabel }}
            </p>
        </div>
    </div>

    {{-- Totals --}}
    <div class="border-t border-gray-200 dark:border-gray-800 pt-4 mb-8">
        <div class="max-w-xs ml-auto space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($order->subtotal_amount, $order->currency ?? 'EUR') }}</span>
            </div>
            @if($order->shipping_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                    <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($order->shipping_amount, $order->currency ?? 'EUR') }}</span>
                </div>
            @endif
            @if($order->tax_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Tax</span>
                    <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($order->tax_amount, $order->currency ?? 'EUR') }}</span>
                </div>
            @endif
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                    <span>Discount</span>
                    <span>-{{ \App\Support\MoneyFormatter::format($order->discount_amount, $order->currency ?? 'EUR') }}</span>
                </div>
            @endif
            <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-200 dark:border-gray-800">
                <span class="text-gray-900 dark:text-white">Total</span>
                <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($order->total_amount, $order->currency ?? 'EUR') }}</span>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-center gap-4">
        <a href="{{ route('storefront.home') }}" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors">
            Continue Shopping
        </a>
        @if(auth('customer')->check())
            <a href="{{ route('storefront.account.orders.show', $order->order_number) }}" class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                View Order
            </a>
        @endif
    </div>
</div>
