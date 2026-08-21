<div class="mx-auto max-w-4xl px-4 py-12 sm:py-20 lg:px-8">
    <header class="text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-4xl font-bold text-green-700 dark:bg-green-950/50 dark:text-green-300" aria-hidden="true">✓</div>
        <p class="mt-8 text-sm font-semibold uppercase tracking-[0.2em] text-green-700 dark:text-green-300">Thank you for your order</p>
        <h1 class="mt-2 text-4xl font-bold tracking-tight text-zinc-950 dark:text-white">Order confirmed</h1>
        <p class="mt-4 text-lg text-zinc-700 dark:text-zinc-300">Order <strong>{{ $order->order_number }}</strong></p>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">We’ve sent a confirmation to {{ $order->email }}.</p>
    </header>

    <div class="mt-10 space-y-6">
        <section class="rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="confirmation-items-heading">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:px-6">
                <h2 id="confirmation-items-heading" class="text-xl font-semibold text-zinc-950 dark:text-white">Order summary</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($order->lines as $line)
                    <div wire:key="confirmation-line-{{ $line->id }}" class="flex items-center gap-4 px-5 py-5 sm:px-6">
                        @if ($line->variant?->product?->media?->first()?->url)
                            <img src="{{ $line->variant->product->media->first()->url }}" alt="{{ $line->product_title }}" class="h-16 w-16 shrink-0 rounded-xl object-cover" loading="lazy">
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-xl text-zinc-400 dark:bg-zinc-900" aria-hidden="true">⌂</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-zinc-950 dark:text-white">{{ $line->product_title }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $line->variant_title }} · Quantity {{ $line->quantity }}</p>
                        </div>
                        <p class="whitespace-nowrap font-semibold text-zinc-950 dark:text-white">{{ $this->formatMoney($line->line_total_amount) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800 sm:p-6" aria-labelledby="confirmation-address-heading">
                <h2 id="confirmation-address-heading" class="text-lg font-semibold text-zinc-950 dark:text-white">Shipping address</h2>
                @if ($order->shipping_address_json)
                    <address class="mt-4 not-italic text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                        <span class="font-medium">{{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}</span><br>
                        {{ $order->shipping_address_json['address1'] ?? '' }}<br>
                        @if (! empty($order->shipping_address_json['address2'])){{ $order->shipping_address_json['address2'] }}<br>@endif
                        {{ $order->shipping_address_json['postal_code'] ?? '' }} {{ $order->shipping_address_json['city'] ?? '' }}<br>
                        {{ $order->shipping_address_json['country_code'] ?? '' }}
                    </address>
                @else
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">No shipping address is required for this order.</p>
                @endif
            </section>

            <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800 sm:p-6" aria-labelledby="confirmation-payment-heading">
                <h2 id="confirmation-payment-heading" class="text-lg font-semibold text-zinc-950 dark:text-white">Payment</h2>
                <p class="mt-4 text-sm text-zinc-700 dark:text-zinc-300">{{ $this->paymentLabel() }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($order->financial_status->value === 'pending')
                        Payment pending
                    @elseif ($order->financial_status->value === 'paid')
                        Payment received
                    @else
                        {{ ucfirst($order->financial_status->value) }}
                    @endif
                </p>
            </section>
        </div>

        @if ($this->isBankTransfer())
            <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-950 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100 sm:p-6" aria-labelledby="bank-transfer-heading">
                <h2 id="bank-transfer-heading" class="text-lg font-semibold">Bank transfer instructions</h2>
                <p class="mt-3 text-sm">Please transfer the total amount to the following account. Complete your transfer within 7 days; your order will be processed once payment is confirmed.</p>
                <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-[auto_1fr] sm:gap-x-6">
                    <dt class="font-semibold">Bank</dt><dd>Mock Bank AG</dd>
                    <dt class="font-semibold">IBAN</dt><dd class="font-mono">DE89 3704 0044 0532 0130 00</dd>
                    <dt class="font-semibold">BIC</dt><dd class="font-mono">COBADEFFXXX</dd>
                    <dt class="font-semibold">Amount</dt><dd>{{ $this->formatMoney($order->total_amount) }}</dd>
                    <dt class="font-semibold">Reference</dt><dd>{{ $order->order_number }}</dd>
                </dl>
            </section>
        @endif

        <section class="rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-900 sm:p-6" aria-labelledby="confirmation-total-heading">
            <h2 id="confirmation-total-heading" class="sr-only">Order totals</h2>
            <dl class="ml-auto max-w-sm space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt><dd class="font-medium text-zinc-950 dark:text-white">{{ $this->formatMoney($order->subtotal_amount) }}</dd></div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between gap-4 text-green-700 dark:text-green-300"><dt>Discount</dt><dd class="font-medium">-{{ $this->formatMoney($order->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt><dd class="font-medium text-zinc-950 dark:text-white">{{ $this->formatMoney($order->shipping_amount) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-zinc-600 dark:text-zinc-400">Tax</dt><dd class="font-medium text-zinc-950 dark:text-white">{{ $this->formatMoney($order->tax_amount) }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-zinc-200 pt-4 text-xl font-bold dark:border-zinc-700"><dt class="text-zinc-950 dark:text-white">Total</dt><dd class="text-zinc-950 dark:text-white">{{ $this->formatMoney($order->total_amount) }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-950" wire:navigate>Continue shopping</a>
        @if ($this->canViewAccountOrder())
            <a href="{{ route('account.order.show', ['orderNumber' => $order->order_number]) }}" class="inline-flex min-h-11 items-center justify-center rounded-full border border-zinc-300 px-6 py-3 font-semibold text-zinc-800 transition hover:border-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:border-zinc-700 dark:text-zinc-200 dark:focus:ring-offset-zinc-950" wire:navigate>View order</a>
        @endif
    </div>
</div>
