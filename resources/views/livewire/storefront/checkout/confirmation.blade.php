<div>
    <div class="mx-auto max-w-2xl">
        {{-- Success Header --}}
        <div class="text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-8 text-green-600 dark:text-green-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="mt-4 text-3xl font-bold tracking-tight">Thank you for your order!</h1>
            <p class="mt-2 text-lg text-zinc-500 dark:text-zinc-400">
                Order <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $order->order_number }}</span>
            </p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                A confirmation email has been sent to <span class="font-medium">{{ $order->email }}</span>
            </p>
        </div>

        {{-- Bank Transfer Details --}}
        @if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer)
            <div class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-900/20">
                <h3 class="font-semibold text-amber-800 dark:text-amber-300">Bank Transfer Details</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">Please transfer the total amount within 7 days using the details below.</p>
                <dl class="mt-3 space-y-1 text-sm">
                    <div class="flex gap-2">
                        <dt class="font-medium text-amber-800 dark:text-amber-300">Bank:</dt>
                        <dd class="text-amber-700 dark:text-amber-400">Mock Bank AG</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-medium text-amber-800 dark:text-amber-300">IBAN:</dt>
                        <dd class="text-amber-700 dark:text-amber-400">DE89 3704 0044 0532 0130 00</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-medium text-amber-800 dark:text-amber-300">BIC:</dt>
                        <dd class="text-amber-700 dark:text-amber-400">COBADEFFXXX</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-medium text-amber-800 dark:text-amber-300">Reference:</dt>
                        <dd class="text-amber-700 dark:text-amber-400">{{ $order->order_number }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-medium text-amber-800 dark:text-amber-300">Amount:</dt>
                        <dd class="text-amber-700 dark:text-amber-400">{{ number_format($order->total_amount / 100, 2) }} EUR</dd>
                    </div>
                </dl>
            </div>
        @endif

        {{-- Order Items --}}
        <div class="mt-8 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">Order Items</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($order->lines as $line)
                    <div wire:key="order-line-{{ $line->id }}" class="flex items-start justify-between gap-4 px-6 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $line->title_snapshot }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty: {{ $line->quantity }}</p>
                        </div>
                        <p class="text-sm font-medium">{{ number_format($line->total_amount / 100, 2) }} EUR</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Order Details --}}
        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
            {{-- Shipping Address --}}
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Shipping Address</h3>
                @php $address = $order->shipping_address_json ?? []; @endphp
                <div class="mt-3 text-sm leading-relaxed">
                    <p>{{ $address['first_name'] ?? '' }} {{ $address['last_name'] ?? '' }}</p>
                    <p>{{ $address['address1'] ?? '' }}</p>
                    @if (! empty($address['address2']))
                        <p>{{ $address['address2'] }}</p>
                    @endif
                    <p>{{ $address['postal_code'] ?? '' }} {{ $address['city'] ?? '' }}</p>
                    <p>{{ $address['country_code'] ?? '' }}</p>
                </div>
            </div>

            {{-- Payment & Totals --}}
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Payment</h3>
                <div class="mt-3 text-sm">
                    <p class="font-medium">
                        @switch($order->payment_method?->value)
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
                                {{ $order->payment_method?->value ?? 'N/A' }}
                        @endswitch
                    </p>
                </div>

                <div class="mt-4 space-y-1 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                        <span>{{ number_format($order->subtotal_amount / 100, 2) }} EUR</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Discount</span>
                            <span class="text-green-600 dark:text-green-400">-{{ number_format($order->discount_amount / 100, 2) }} EUR</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                        <span>{{ number_format($order->shipping_amount / 100, 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                        <span>{{ number_format($order->tax_amount / 100, 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-1 font-semibold dark:border-zinc-700">
                        <span>Total</span>
                        <span>{{ number_format($order->total_amount / 100, 2) }} EUR</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Continue Shopping --}}
        <div class="mt-8 text-center">
            <flux:button variant="primary" href="{{ route('storefront.collections.index') }}">
                Continue Shopping
            </flux:button>
        </div>
    </div>
</div>
