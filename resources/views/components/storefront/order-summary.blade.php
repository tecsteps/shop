{{--
    Checkout order summary sidebar. Phase 4 integration point: the checkout
    page passes line items and totals derived from the Checkout model. Lines
    are arrays with keys: title, variant (nullable), quantity, image_url
    (nullable), line_total_amount.
--}}
@props([
    'lines' => [],
    'currency' => 'EUR',
    'subtotalAmount' => null,
    'discountAmount' => null,
    'discountLabel' => null,
    'shippingAmount' => null,
    'taxAmount' => null,
    'totalAmount' => null,
    'showDiscountInput' => true,
])

<aside {{ $attributes->class('rounded-2xl bg-zinc-50 p-6 dark:bg-zinc-900') }} aria-label="{{ __('Order summary') }}">
    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Order Summary') }}</h2>

    @if ($lines !== [])
        <ul class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach ($lines as $line)
                <li class="flex items-center gap-3 py-3">
                    <div class="relative size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                        @if (filled($line['image_url'] ?? null))
                            <img src="{{ $line['image_url'] }}" alt="" class="size-full object-cover" loading="lazy" />
                        @endif
                        <span class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-zinc-700 text-[10px] font-semibold text-white dark:bg-zinc-600">
                            {{ $line['quantity'] }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $line['title'] }}</p>
                        @if (filled($line['variant'] ?? null))
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant'] }}</p>
                        @endif
                    </div>
                    <x-storefront.price :amount="$line['line_total_amount']" :currency="$currency" class="text-sm" />
                </li>
            @endforeach
        </ul>
    @endif

    @if ($showDiscountInput)
        {{-- Phase 4 integration point: discount code apply/remove actions --}}
        <div class="mt-4 flex gap-2">
            <label for="order-summary-discount-code" class="sr-only">{{ __('Discount code') }}</label>
            <input
                id="order-summary-discount-code"
                type="text"
                placeholder="{{ __('Discount code') }}"
                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
            />
            <button
                type="button"
                class="shrink-0 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                {{ __('Apply') }}
            </button>
        </div>
    @endif

    <dl class="mt-6 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800" aria-live="polite">
        @if ($subtotalAmount !== null)
            <div class="flex items-center justify-between">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</dt>
                <dd><x-storefront.price :amount="$subtotalAmount" :currency="$currency" class="text-sm" /></dd>
            </div>
        @endif
        @if ($discountAmount !== null && $discountAmount > 0)
            <div class="flex items-center justify-between text-green-700 dark:text-green-400">
                <dt>{{ __('Discount') }}{{ filled($discountLabel) ? " ({$discountLabel})" : '' }}</dt>
                <dd class="font-medium">-{{ \App\Support\Storefront\PriceFormatter::format($discountAmount, $currency) }}</dd>
            </div>
        @endif
        <div class="flex items-center justify-between">
            <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Shipping') }}</dt>
            <dd class="text-zinc-900 dark:text-white">
                @if ($shippingAmount !== null)
                    <x-storefront.price :amount="$shippingAmount" :currency="$currency" class="text-sm" />
                @else
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Calculated at next step') }}</span>
                @endif
            </dd>
        </div>
        @if ($taxAmount !== null)
            <div class="flex items-center justify-between">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Tax') }}</dt>
                <dd><x-storefront.price :amount="$taxAmount" :currency="$currency" class="text-sm" /></dd>
            </div>
        @endif
        @if ($totalAmount !== null)
            <div class="flex items-center justify-between border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                <dt class="font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</dt>
                <dd><x-storefront.price :amount="$totalAmount" :currency="$currency" /></dd>
            </div>
        @endif
    </dl>
</aside>
