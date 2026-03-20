<div>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        {{-- Success header --}}
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Thank you for your order!</h1>
            <p class="mt-2 text-lg text-gray-500 dark:text-gray-400">Order {{ $orderNumber }}</p>
            @if($email)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">We've sent a confirmation to {{ $email }}</p>
            @endif
        </div>

        {{-- Order items --}}
        <div class="mt-8 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Summary</h2>
            <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($lines as $line)
                    <div wire:key="confirm-line-{{ $loop->index }}" class="flex items-center justify-between py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $line['title'] }}</p>
                            @if($line['sku'])
                                <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $line['sku'] }}</p>
                            @endif
                            <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ $line['quantity'] }}</p>
                        </div>
                        <span class="ml-4 text-sm font-medium text-gray-900 dark:text-white">${{ number_format($line['total'] / 100, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Address and payment --}}
        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            {{-- Shipping address --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Shipping Address</h3>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}</p>
                    <p>{{ $shippingAddress['address1'] ?? '' }}</p>
                    @if(!empty($shippingAddress['address2']))
                        <p>{{ $shippingAddress['address2'] }}</p>
                    @endif
                    <p>{{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['province'] ?? '' }} {{ $shippingAddress['postal_code'] ?? '' }}</p>
                    <p>{{ $shippingAddress['country'] ?? '' }}</p>
                </div>
            </div>

            {{-- Payment method --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payment Method</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if($paymentMethod === 'credit_card')
                        Credit Card
                    @elseif($paymentMethod === 'paypal')
                        PayPal
                    @elseif($paymentMethod === 'bank_transfer')
                        Bank Transfer
                    @else
                        {{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}
                    @endif
                </p>
            </div>
        </div>

        {{-- Bank transfer instructions --}}
        @if($paymentMethod === 'bank_transfer')
            <div class="mt-6">
                <flux:callout variant="info" heading="Bank Transfer Instructions">
                    <p>Please transfer the total amount to the following account:</p>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex gap-2">
                            <dt class="font-medium text-gray-900 dark:text-white">Bank:</dt>
                            <dd>Mock Bank AG</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium text-gray-900 dark:text-white">IBAN:</dt>
                            <dd>DE89 3704 0044 0532 0130 00</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium text-gray-900 dark:text-white">BIC:</dt>
                            <dd>COBADEFFXXX</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium text-gray-900 dark:text-white">Amount:</dt>
                            <dd>${{ number_format($totalAmount / 100, 2) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-medium text-gray-900 dark:text-white">Reference:</dt>
                            <dd>{{ $orderNumber }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3">Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.</p>
                </flux:callout>
            </div>
        @endif

        {{-- Totals --}}
        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span class="text-gray-900 dark:text-white">${{ number_format($subtotalAmount / 100, 2) }}</span>
                </div>
                @if($discountAmount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Discount</span>
                        <span class="text-green-600 dark:text-green-400">-${{ number_format($discountAmount / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                    <span class="text-gray-900 dark:text-white">${{ number_format($shippingAmount / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Tax</span>
                    <span class="text-gray-900 dark:text-white">${{ number_format($taxAmount / 100, 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                    <span class="text-base font-semibold text-gray-900 dark:text-white">Total</span>
                    <span class="text-base font-semibold text-gray-900 dark:text-white">${{ number_format($totalAmount / 100, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="mt-8 flex items-center justify-center gap-4">
            <flux:button href="{{ route('storefront.home') }}" variant="primary">Continue shopping</flux:button>
            @if($customerId)
                <flux:button href="{{ route('customer.orders.show', $orderNumber) }}" variant="ghost">View order</flux:button>
            @endif
        </div>
    </div>
</div>
