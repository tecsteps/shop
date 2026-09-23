@php
    $orderLines = data_get($checkout ?? null, 'cart.lines', data_get($checkout ?? null, 'lines', data_get($checkout ?? null, 'items', [])));
    $currency = data_get($currentStore ?? null, 'currency', 'EUR');
    $subtotal = (int) data_get($checkout ?? null, 'subtotal_amount', data_get($checkout ?? null, 'subtotal', 0));
    $discount = (int) data_get($checkout ?? null, 'discount_amount', 0);
    $shipping = data_get($checkout ?? null, 'shipping_amount');
    $tax = data_get($checkout ?? null, 'tax_amount');
    $total = (int) data_get($checkout ?? null, 'total_amount', $subtotal - $discount + (int) $shipping + (int) $tax);
@endphp
<section aria-labelledby="checkout-order-summary" class="rounded-3xl bg-stone-100 p-5 sm:p-6 dark:bg-stone-900">
    <h2 id="checkout-order-summary" class="text-lg font-semibold tracking-tight text-stone-950 dark:text-white">Order summary</h2>
    <ul class="mt-5 divide-y divide-stone-200 dark:divide-stone-800">
        @forelse ($orderLines as $line)
            @php
                $lineProduct = data_get($line, 'product', data_get($line, 'variant.product'));
                $lineTitle = data_get($line, 'title', data_get($lineProduct, 'title', 'Item'));
                $lineImage = data_get($line, 'image_url', data_get($lineProduct, 'media.0.url', data_get($lineProduct, 'image_url')));
                $lineQuantity = (int) data_get($line, 'quantity', 1);
                $lineAmount = (int) data_get($line, 'line_total_amount', data_get($line, 'total_amount', data_get($line, 'price_amount', 0) * $lineQuantity));
            @endphp
            <li class="flex gap-3 py-4 first:pt-0 last:pb-0">
                <div class="relative size-14 shrink-0 overflow-hidden rounded-xl bg-white dark:bg-stone-800">
                    @if ($lineImage)<img src="{{ $lineImage }}" alt="{{ data_get($line, 'image_alt', $lineTitle) }}" loading="lazy" class="size-full object-cover">@else <span class="flex size-full items-center justify-center text-stone-300 dark:text-stone-600">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-6'])</span>@endif
                    <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-stone-700 px-1 text-[10px] font-semibold leading-5 text-white dark:bg-stone-200 dark:text-stone-950" aria-label="Quantity {{ $lineQuantity }}">{{ $lineQuantity }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-stone-900 dark:text-stone-100">{{ $lineTitle }}</p>
                    @if (data_get($line, 'variant_title') || data_get($line, 'options'))
                        <p class="mt-1 truncate text-xs text-stone-500 dark:text-stone-400">{{ data_get($line, 'variant_title', is_array(data_get($line, 'options')) ? implode(' / ', data_get($line, 'options')) : '') }}</p>
                    @endif
                </div>
                <div class="shrink-0 text-sm font-medium">@include('storefront.components.price', ['amount' => $lineAmount, 'currency' => $currency])</div>
            </li>
        @empty
            <li class="py-4 text-sm text-stone-600 dark:text-stone-300">Your order summary will appear here.</li>
        @endforelse
    </ul>

    @if ($showDiscountInput ?? true)
        <form wire:submit="applyDiscount" class="mt-5 flex gap-2 border-t border-stone-200 pt-5 dark:border-stone-800">
            <label for="checkout-discount-code" class="sr-only">Discount code</label>
            <input id="checkout-discount-code" type="text" wire:model="discountCode" autocomplete="off" placeholder="Discount code" class="storefront-input min-w-0 flex-1">
            <button type="submit" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-stone-300 px-4 text-sm font-semibold hover:bg-white focus-visible:outline-2 focus-visible:outline-blue-700 dark:border-stone-700 dark:hover:bg-stone-800">Apply</button>
        </form>
        @error('discountCode')<p class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
    @endif

    <dl aria-live="polite" class="mt-5 grid gap-3 border-t border-stone-200 pt-5 text-sm dark:border-stone-800">
        <div class="flex items-center justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $subtotal, 'currency' => $currency])</dd></div>
        @if ($discount > 0)<div class="flex items-center justify-between gap-4 text-emerald-800 dark:text-emerald-300"><dt>Discount</dt><dd>−@include('storefront.components.price', ['amount' => $discount, 'currency' => $currency])</dd></div>@endif
        <div class="flex items-center justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Shipping</dt><dd class="text-right">@if ($shipping !== null) @include('storefront.components.price', ['amount' => (int) $shipping, 'currency' => $currency]) @else <span class="text-stone-500">Calculated at next step</span> @endif</dd></div>
        <div class="flex items-center justify-between gap-4"><dt class="text-stone-600 dark:text-stone-300">Tax</dt><dd class="text-right">@if ($tax !== null) @include('storefront.components.price', ['amount' => (int) $tax, 'currency' => $currency]) @else <span class="text-stone-500">Calculated after address</span> @endif</dd></div>
        <div class="flex items-center justify-between gap-4 border-t border-stone-200 pt-4 text-base font-semibold dark:border-stone-800"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $total, 'currency' => $currency])</dd></div>
    </dl>
</section>
