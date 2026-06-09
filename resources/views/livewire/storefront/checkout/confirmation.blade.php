@php
    use App\Enums\FinancialStatus;
    use App\Enums\PaymentMethod;
    use App\Support\Storefront\PriceFormatter;

    $shippingAddress = $order->shipping_address_json ?? [];
    $isBankTransfer = $order->payment_method === PaymentMethod::BankTransfer;
@endphp

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    {{-- Success header --}}
    <div class="text-center">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
            <svg class="size-9 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </span>
        <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Thank you for your order!') }}</h1>
        <p class="mt-2 text-lg text-zinc-600 dark:text-zinc-400">{{ __('Order :number', ['number' => $order->order_number]) }}</p>
        @if (filled($order->email))
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __("We've sent a confirmation to :email", ['email' => $order->email]) }}</p>
        @endif
    </div>

    {{-- Items --}}
    <section class="mt-10 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800" aria-labelledby="confirmation-summary">
        <h2 id="confirmation-summary" class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Order summary') }}</h2>
        <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800" role="list">
            @foreach ($items as $item)
                <li class="flex items-center gap-4 py-3">
                    @if ($item['image_url'] !== null)
                        <img src="{{ $item['image_url'] }}" alt="" class="size-12 shrink-0 rounded-lg object-cover" />
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <svg class="size-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                            </svg>
                        </span>
                    @endif
                    <span class="min-w-0 flex-1 text-sm text-zinc-900 dark:text-white">
                        <span class="block truncate font-medium">{{ $item['title'] }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Qty') }}: {{ $item['quantity'] }}</span>
                    </span>
                    <x-storefront.price :amount="$item['total_amount']" :currency="$order->currency" class="text-sm font-medium" />
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Address and payment method --}}
    <section class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Shipping address') }}</h2>
            @if ($shippingAddress !== [])
                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                    {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br />
                    {{ $shippingAddress['address1'] ?? '' }}@if (filled($shippingAddress['address2'] ?? ''))<br />{{ $shippingAddress['address2'] }}@endif<br />
                    {{ $shippingAddress['postal_code'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['country_code'] ?? '' }}
                </p>
            @else
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No shipping required (digital order)') }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Payment method') }}</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $order->payment_method->label() }}</p>
        </div>
    </section>

    {{-- Bank transfer instructions --}}
    @if ($isBankTransfer && $order->financial_status === FinancialStatus::Pending)
        <section class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-900 dark:bg-blue-950/40" aria-labelledby="bank-transfer-instructions">
            <h2 id="bank-transfer-instructions" class="flex items-center gap-2 text-base font-semibold text-blue-900 dark:text-blue-200">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                {{ __('Bank Transfer Instructions') }}
            </h2>
            <p class="mt-2 text-sm text-blue-900/80 dark:text-blue-200/80">{{ __('Please transfer the total amount to the following account:') }}</p>
            <dl class="mt-3 space-y-1 text-sm text-blue-900 dark:text-blue-200">
                <div class="flex gap-2"><dt class="w-24 font-medium">{{ __('Bank') }}:</dt><dd>Mock Bank AG</dd></div>
                <div class="flex gap-2"><dt class="w-24 font-medium">IBAN:</dt><dd>DE89 3704 0044 0532 0130 00</dd></div>
                <div class="flex gap-2"><dt class="w-24 font-medium">BIC:</dt><dd>COBADEFFXXX</dd></div>
                <div class="flex gap-2"><dt class="w-24 font-medium">{{ __('Amount') }}:</dt><dd>{{ PriceFormatter::format($order->total_amount, $order->currency) }}</dd></div>
                <div class="flex gap-2"><dt class="w-24 font-medium">{{ __('Reference') }}:</dt><dd>{{ $order->order_number }}</dd></div>
            </dl>
            <p class="mt-3 text-sm text-blue-900/80 dark:text-blue-200/80">
                {{ __('Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.') }}
            </p>
        </section>
    @endif

    {{-- Totals --}}
    <section class="mt-4 rounded-2xl bg-zinc-50 p-6 dark:bg-zinc-900" aria-label="{{ __('Order totals') }}">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <dt>{{ __('Subtotal') }}</dt>
                <dd><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" /></dd>
            </div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-green-700 dark:text-green-400">
                    <dt>{{ __('Discount') }}</dt>
                    <dd>-{{ PriceFormatter::format($order->discount_amount, $order->currency) }}</dd>
                </div>
            @endif
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <dt>{{ __('Shipping') }}</dt>
                <dd><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" /></dd>
            </div>
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <dt>{{ __('Tax') }}</dt>
                <dd><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" /></dd>
            </div>
            <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">
                <dt>{{ __('Total') }}</dt>
                <dd><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></dd>
            </div>
        </dl>
    </section>

    {{-- Actions --}}
    <div class="mt-8 flex items-center justify-center gap-4">
        <a
            href="{{ route('home') }}"
            class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
            style="background-color: var(--sf-primary, #2563eb);"
        >
            {{ __('Continue shopping') }}
        </a>
        @auth('customer')
            <a href="{{ route('storefront.account.index') }}" class="text-sm font-medium text-blue-600 transition hover:text-blue-700 dark:text-blue-400">
                {{ __('View order') }}
            </a>
        @endauth
    </div>
</div>
