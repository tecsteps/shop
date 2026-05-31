<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8" data-testid="confirmation">
    {{-- Minimal functional order confirmation. Storefront teammate (task #6)
         restyles; the data shown stays the same. --}}
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('Thank you for your order!') }}</h1>
        <p class="mt-2 text-zinc-500">{{ __('Order') }} <span data-testid="order-number">{{ $order->order_number }}</span></p>
    </div>

    <section class="mb-8 rounded-lg border border-zinc-200">
        @foreach ($lines as $line)
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 last:border-0" wire:key="order-line-{{ $line->id }}">
                <div>
                    <p class="text-sm font-medium">{{ $line->title_snapshot }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Qty') }}: {{ $line->quantity }}</p>
                </div>
                <span class="text-sm">{{ \App\Support\Storefront\PriceFormatter::format($line->total_amount, $order->currency) }}</span>
            </div>
        @endforeach
    </section>

    <section class="mb-8 rounded-lg bg-zinc-50 p-4 text-sm">
        <div class="flex justify-between py-1"><span>{{ __('Subtotal') }}</span><span>{{ \App\Support\Storefront\PriceFormatter::format($order->subtotal_amount, $order->currency) }}</span></div>
        @if ($order->discount_amount > 0)
            <div class="flex justify-between py-1 text-green-700"><span>{{ __('Discount') }}</span><span>-{{ \App\Support\Storefront\PriceFormatter::format($order->discount_amount, $order->currency) }}</span></div>
        @endif
        <div class="flex justify-between py-1"><span>{{ __('Shipping') }}</span><span>{{ \App\Support\Storefront\PriceFormatter::format($order->shipping_amount, $order->currency) }}</span></div>
        <div class="flex justify-between py-1"><span>{{ __('Tax') }}</span><span>{{ \App\Support\Storefront\PriceFormatter::format($order->tax_amount, $order->currency) }}</span></div>
        <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2 font-semibold"><span>{{ __('Total') }}</span><span data-testid="order-total">{{ \App\Support\Storefront\PriceFormatter::format($order->total_amount, $order->currency) }}</span></div>
    </section>

    @if ($isBankTransfer && $bankDetails)
        <section class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm" data-testid="bank-transfer-instructions">
            <h2 class="mb-2 font-semibold text-amber-900">{{ __('Bank transfer instructions') }}</h2>
            <dl class="grid grid-cols-2 gap-1 text-amber-900">
                <dt>{{ __('Bank') }}</dt><dd>{{ $bankDetails['bank_name'] }}</dd>
                <dt>{{ __('IBAN') }}</dt><dd>{{ $bankDetails['iban'] }}</dd>
                <dt>{{ __('BIC') }}</dt><dd>{{ $bankDetails['bic'] }}</dd>
                <dt>{{ __('Reference') }}</dt><dd>{{ $order->order_number }}</dd>
                <dt>{{ __('Amount') }}</dt><dd>{{ \App\Support\Storefront\PriceFormatter::format($order->total_amount, $order->currency) }}</dd>
            </dl>
        </section>
    @endif
</div>
