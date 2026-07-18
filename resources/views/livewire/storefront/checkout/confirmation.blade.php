@php
    $paymentLabels = [
        'credit_card' => 'Credit Card',
        'paypal' => 'PayPal',
        'bank_transfer' => 'Bank Transfer',
    ];
@endphp

<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="text-center">
        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
            <flux:icon name="check" class="size-8 text-green-600 dark:text-green-400" />
        </div>
        <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">Thank you for your order!</h1>
        <p class="mt-2 text-lg text-zinc-500 dark:text-zinc-400">Order {{ $order->order_number }}</p>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">We've sent a confirmation to {{ $order->email }}</p>
    </div>

    <div class="mt-10 rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
        <ul class="mt-4 space-y-3">
            @foreach ($order->lines as $line)
                <li class="flex items-center gap-3">
                    @php $thumb = $line->variant?->product?->media->sortBy('position')->first(); @endphp
                    <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @if ($thumb)
                            <img src="{{ $thumb->url ?? Storage::url($thumb->storage_key) }}" alt="" class="size-full object-cover" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }} &times; {{ $line->quantity }}</p>
                    </div>
                    <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                </li>
            @endforeach
        </ul>

        <div class="mt-6 grid gap-6 border-t border-zinc-200 pt-6 sm:grid-cols-2 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Shipping Address</h3>
                @php $address = $order->shipping_address_json ?? []; @endphp
                <address class="mt-2 text-sm text-zinc-600 not-italic dark:text-zinc-400">
                    {{ $address['first_name'] ?? '' }} {{ $address['last_name'] ?? '' }}<br />
                    {{ $address['address1'] ?? '' }}<br />
                    @if (! empty($address['address2']))
                        {{ $address['address2'] }}<br />
                    @endif
                    {{ $address['postal_code'] ?? '' }} {{ $address['city'] ?? '' }}<br />
                    {{ $address['country'] ?? '' }}
                </address>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Payment Method</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $paymentLabels[$order->payment_method->value] ?? $order->payment_method->value }}</p>
            </div>
        </div>

        @if ($order->payment_method->value === 'bank_transfer')
            <flux:callout variant="info" icon="information-circle" class="mt-6">
                <div class="font-semibold">Bank Transfer Instructions</div>
                <p class="mt-1">Please transfer the total amount to the following account:</p>
                <dl class="mt-2 space-y-1 text-sm">
                    <div><dt class="inline font-medium">Bank:</dt> <dd class="inline">Mock Bank AG</dd></div>
                    <div><dt class="inline font-medium">IBAN:</dt> <dd class="inline">DE89 3704 0044 0532 0130 00</dd></div>
                    <div><dt class="inline font-medium">BIC:</dt> <dd class="inline">COBADEFFXXX</dd></div>
                    <div><dt class="inline font-medium">Amount:</dt> <dd class="inline"><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></dd></div>
                    <div><dt class="inline font-medium">Reference:</dt> <dd class="inline">{{ $order->order_number }}</dd></div>
                </dl>
                <p class="mt-2">Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.</p>
            </flux:callout>
        @endif

        <dl class="mt-6 space-y-2 border-t border-zinc-200 pt-6 text-sm dark:border-zinc-800">
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" /></dd>
            </div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-green-600 dark:text-green-400">
                    <dt>Discount</dt>
                    <dd>-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</dd>
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Shipping</dt>
                <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-zinc-500 dark:text-zinc-400">Tax</dt>
                <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" /></dd>
            </div>
            <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
                <dt>Total</dt>
                <dd><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></dd>
            </div>
        </dl>
    </div>

    <div class="mt-8 flex justify-center gap-4">
        <flux:button :href="route('home')" wire:navigate variant="primary">Continue shopping</flux:button>
        @auth('customer')
            <flux:button :href="route('storefront.account.orders.show', $order)" wire:navigate variant="filled">View order</flux:button>
        @endauth
    </div>
</div>
