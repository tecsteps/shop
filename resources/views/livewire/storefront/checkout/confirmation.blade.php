@php
    use App\Support\Storefront\PriceFormatter;
    $money = fn ($amount) => PriceFormatter::format((int) $amount, $order->currency);
    $shipping = $order->shipping_address_json ?? [];
@endphp

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8" data-testid="confirmation">
    <div class="text-center">
        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
            <flux:icon.check class="size-8 text-green-600 dark:text-green-400" />
        </div>
        <h1 class="mt-6 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ __('Thank you for your order!') }}</h1>
        <p class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('Order') }} <span data-testid="order-number" class="font-medium text-zinc-700 dark:text-zinc-300">{{ $order->order_number }}</span></p>
        @if ($order->email)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __("We've sent a confirmation to :email", ['email' => $order->email]) }}</p>
        @endif
    </div>

    {{-- Items. --}}
    <section class="mt-10 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        @foreach ($lines as $line)
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 last:border-0 dark:border-zinc-800" wire:key="order-line-{{ $line->id }}">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Qty') }}: {{ $line->quantity }}</p>
                </div>
                <span class="text-sm text-zinc-900 dark:text-white">{{ $money($line->total_amount) }}</span>
            </div>
        @endforeach
    </section>

    {{-- Address + payment. --}}
    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Shipping address') }}</h2>
            <address class="mt-2 text-sm not-italic text-zinc-700 dark:text-zinc-300">
                {{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}<br>
                {{ $shipping['address1'] ?? '' }}<br>
                {{ trim(($shipping['city'] ?? '').', '.($shipping['postal_code'] ?? ''), ', ') }}<br>
                {{ $shipping['country'] ?? '' }}
            </address>
        </div>
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Payment method') }}</h2>
            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ ucwords(str_replace('_', ' ', $order->payment_method->value)) }}</p>
        </div>
    </div>

    {{-- Bank transfer instructions. --}}
    @if ($isBankTransfer && $bankDetails)
        <section class="mt-6 rounded-xl border border-blue-300 bg-blue-50 p-5 text-sm dark:border-blue-900 dark:bg-blue-950" data-testid="bank-transfer-instructions">
            <h2 class="flex items-center gap-2 font-semibold text-blue-900 dark:text-blue-200">
                <flux:icon.information-circle class="size-5" />
                {{ __('Bank Transfer Instructions') }}
            </h2>
            <p class="mt-2 text-blue-900 dark:text-blue-200">{{ __('Please transfer the total amount to the following account:') }}</p>
            <dl class="mt-3 grid grid-cols-[6rem_1fr] gap-1 text-blue-900 dark:text-blue-200">
                <dt class="font-medium">{{ __('Bank') }}</dt><dd>{{ $bankDetails['bank_name'] }}</dd>
                <dt class="font-medium">{{ __('IBAN') }}</dt><dd>{{ $bankDetails['iban'] }}</dd>
                <dt class="font-medium">{{ __('BIC') }}</dt><dd>{{ $bankDetails['bic'] }}</dd>
                <dt class="font-medium">{{ __('Amount') }}</dt><dd>{{ $money($order->total_amount) }}</dd>
                <dt class="font-medium">{{ __('Reference') }}</dt><dd>{{ $order->order_number }}</dd>
            </dl>
            <p class="mt-3 text-blue-900 dark:text-blue-200">{{ __('Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.') }}</p>
        </section>
    @endif

    {{-- Totals. --}}
    <dl class="mt-6 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Subtotal') }}</dt><dd>{{ $money($order->subtotal_amount) }}</dd></div>
        @if ($order->discount_amount > 0)
            <div class="flex justify-between text-green-600 dark:text-green-400"><dt>{{ __('Discount') }}</dt><dd>-{{ $money($order->discount_amount) }}</dd></div>
        @endif
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Shipping') }}</dt><dd>{{ $money($order->shipping_amount) }}</dd></div>
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Tax') }}</dt><dd>{{ $money($order->tax_amount) }}</dd></div>
        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white"><dt>{{ __('Total') }}</dt><dd data-testid="order-total">{{ $money($order->total_amount) }}</dd></div>
    </dl>

    {{-- Actions. --}}
    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
        <a href="{{ route('storefront.home') }}" wire:navigate
           class="rounded-lg bg-blue-600 px-6 py-3 text-center font-semibold text-white transition hover:bg-blue-700">{{ __('Continue shopping') }}</a>
        @auth('customer')
            <a href="{{ route('account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" wire:navigate
               class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">{{ __('View order') }}</a>
        @endauth
    </div>
</div>
