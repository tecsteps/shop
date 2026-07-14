@props([
    'checkout',
    'showDiscountInput' => true,
    'headingId' => 'order-summary-heading',
])

@php
    $errors ??= new \Illuminate\Support\ViewErrorBag();
    $cart = data_get($checkout, 'cart');
    $lines = collect(data_get($cart, 'lines', []));
    $currency = (string) data_get($cart, 'currency', data_get($checkout, 'currency', 'USD'));
    $discountCode = data_get($checkout, 'discount_code');
    $enumValue = static fn (mixed $value): mixed => $value instanceof \BackedEnum ? $value->value : $value;

    $totals = data_get($checkout, 'totals_json', []);

    if (is_string($totals)) {
        $totals = json_decode($totals, true) ?: [];
    } elseif (is_object($totals)) {
        $totals = (array) $totals;
    }

    $lineSubtotal = $lines->sum(fn (mixed $line): int => (int) data_get(
        $line,
        'line_subtotal_amount',
        (int) data_get($line, 'unit_price_amount', 0) * (int) data_get($line, 'quantity', 1),
    ));

    $subtotal = (int) data_get($totals, 'subtotal', data_get($totals, 'subtotal_amount', $lineSubtotal));
    $discount = abs((int) data_get($totals, 'discount', data_get($totals, 'discount_amount', 0)));
    $hasShippingAmount = array_key_exists('shipping', $totals) || array_key_exists('shipping_amount', $totals);
    $shipping = $hasShippingAmount
        ? (int) data_get($totals, 'shipping', data_get($totals, 'shipping_amount', 0))
        : null;
    $tax = (int) data_get($totals, 'tax', data_get($totals, 'tax_amount', 0));
    $total = (int) data_get(
        $totals,
        'total',
        data_get($totals, 'total_amount', $subtotal - $discount + ($shipping ?? 0) + $tax),
    );

    $mediaUrl = static function (mixed $item): ?string {
        $url = data_get($item, 'url');

        if (filled($url)) {
            return (string) $url;
        }

        $storageKey = data_get($item, 'storage_key');

        if (blank($storageKey)) {
            return null;
        }

        if (str_starts_with((string) $storageKey, 'http://') || str_starts_with((string) $storageKey, 'https://') || str_starts_with((string) $storageKey, '/')) {
            return (string) $storageKey;
        }

        return asset('storage/'.ltrim((string) $storageKey, '/'));
    };
@endphp

<aside {{ $attributes->class('rounded-2xl bg-zinc-100 p-5 sm:p-6 dark:bg-zinc-900') }} aria-labelledby="{{ $headingId }}">
    <h2 id="{{ $headingId }}" class="text-lg font-semibold text-zinc-950 dark:text-white">
        {{ __('Order Summary') }}
    </h2>

    <ul class="mt-5 divide-y divide-zinc-200 border-y border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800" role="list">
        @forelse ($lines as $line)
            @php
                $variant = data_get($line, 'variant');
                $product = data_get($variant, 'product');
                $title = (string) data_get($product, 'title', data_get($line, 'title', __('Product')));
                $quantity = max(1, (int) data_get($line, 'quantity', 1));
                $lineTotal = (int) data_get($line, 'line_total_amount', (int) data_get($line, 'unit_price_amount', 0) * $quantity);
                $productMedia = collect(data_get($product, 'media', []))
                    ->filter(fn (mixed $item): bool => $enumValue(data_get($item, 'type', 'image')) === 'image' && $enumValue(data_get($item, 'status', 'ready')) === 'ready')
                    ->sortBy(fn (mixed $item): int => (int) data_get($item, 'position', 0))
                    ->first();
                $imageUrl = $mediaUrl($productMedia);
                $imageAlt = (string) data_get($productMedia, 'alt_text', $title);
                $optionValues = collect(data_get($variant, 'optionValues', data_get($variant, 'option_values', [])))
                    ->map(fn (mixed $optionValue): string => (string) data_get($optionValue, 'value', ''))
                    ->filter()
                    ->implode(' / ');
            @endphp

            <li class="flex gap-3 py-4">
                <div class="relative size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="size-full object-cover" loading="lazy" decoding="async">
                    @else
                        <span class="flex size-full items-center justify-center text-zinc-400 dark:text-zinc-600" aria-hidden="true">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25"><path d="M6.75 8.25h10.5l.75 12H6l.75-12Z" stroke-linejoin="round" /><path d="M9 9V6a3 3 0 0 1 6 0v3" stroke-linecap="round" /></svg>
                        </span>
                    @endif
                    <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-700 px-1.5 py-0.5 text-[0.6875rem] font-semibold leading-4 text-white ring-2 ring-zinc-100 dark:bg-zinc-200 dark:text-zinc-900 dark:ring-zinc-900" aria-label="{{ trans_choice(':count item|:count items', $quantity, ['count' => $quantity]) }}">
                        {{ $quantity }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="storefront-line-clamp-1 text-sm font-medium text-zinc-950 dark:text-white">{{ $title }}</p>
                    @if ($optionValues !== '')
                        <p class="mt-0.5 storefront-line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $optionValues }}</p>
                    @endif
                </div>

                <x-storefront.price :amount="$lineTotal" :$currency class="shrink-0 text-sm" />
            </li>
        @empty
            <li class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Your cart is empty.') }}
            </li>
        @endforelse
    </ul>

    @if ($showDiscountInput)
        <div class="border-b border-zinc-200 py-5 dark:border-zinc-800">
            @if (filled($discountCode))
                <div class="flex items-center justify-between gap-3 text-sm text-emerald-700 dark:text-emerald-400">
                    <span class="min-w-0 truncate font-medium">{{ $discountCode }}</span>
                    <button type="button" class="shrink-0 rounded-sm font-medium underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--storefront-primary)]" wire:click="removeDiscount" wire:loading.attr="disabled" wire:target="removeDiscount">
                        {{ __('Remove') }}
                    </button>
                </div>
            @else
                <form class="flex items-start gap-2" wire:submit="applyDiscount">
                    <div class="min-w-0 flex-1">
                        <label for="checkout-discount-code" class="sr-only">{{ __('Discount code') }}</label>
                        <input id="checkout-discount-code" type="text" autocomplete="off" placeholder="{{ __('Discount code') }}" wire:model.blur="discountCode" class="storefront-field-input py-2 text-sm" @if ($errors->has('discountCode')) aria-invalid="true" aria-describedby="checkout-discount-code-error" @endif>
                        @if ($errors->has('discountCode'))
                            <p id="checkout-discount-code-error" class="storefront-field-error">{{ $errors->first('discountCode') }}</p>
                        @endif
                    </div>
                    <button type="submit" class="storefront-button-secondary shrink-0 py-2" wire:loading.attr="disabled" wire:target="applyDiscount">
                        <span wire:loading.remove wire:target="applyDiscount">{{ __('Apply') }}</span>
                        <span wire:loading wire:target="applyDiscount">{{ __('Applying…') }}</span>
                    </button>
                </form>
            @endif
        </div>
    @endif

    <dl class="mt-5 space-y-3 text-sm" aria-live="polite" aria-atomic="true">
        <div class="flex items-center justify-between gap-4 text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Subtotal') }}</dt>
            <dd><x-storefront.price :amount="$subtotal" :$currency /></dd>
        </div>

        @if ($discount > 0)
            <div class="flex items-center justify-between gap-4 text-emerald-700 dark:text-emerald-400">
                <dt>{{ filled($discountCode) ? __('Discount (:code)', ['code' => $discountCode]) : __('Discount') }}</dt>
                <dd><x-storefront.price :amount="-$discount" :$currency /></dd>
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Shipping') }}</dt>
            <dd class="text-right">
                @if ($shipping === null)
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Calculated at next step') }}</span>
                @else
                    <x-storefront.price :amount="$shipping" :$currency />
                @endif
            </dd>
        </div>

        <div class="flex items-center justify-between gap-4 text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Tax') }}</dt>
            <dd><x-storefront.price :amount="$tax" :$currency /></dd>
        </div>

        <div class="flex items-center justify-between gap-4 border-t border-zinc-300 pt-4 text-base font-semibold text-zinc-950 dark:border-zinc-700 dark:text-white">
            <dt>{{ __('Total') }}</dt>
            <dd><x-storefront.price :amount="$total" :$currency /></dd>
        </div>
    </dl>
</aside>
