@props([
    'subtotal' => 0,
    'shipping' => 0,
    'tax' => 0,
    'discount' => 0,
    'total' => null,
    'currency' => null,
])

@php
    $currency = $currency ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
    $total = $total ?? (int) $subtotal + (int) $shipping + (int) $tax - (int) $discount;
@endphp

<dl {{ $attributes->class(['space-y-2 rounded-lg border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-800 dark:bg-zinc-900']) }}>
    <div class="flex justify-between">
        <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
        <dd><x-storefront.price :amount="$subtotal" :currency="$currency" /></dd>
    </div>
    @if ((int) $discount > 0)
        <div class="flex justify-between">
            <dt class="text-zinc-600 dark:text-zinc-400">Discount</dt>
            <dd class="text-emerald-600 dark:text-emerald-400">-<x-storefront.price :amount="$discount" :currency="$currency" /></dd>
        </div>
    @endif
    <div class="flex justify-between">
        <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
        <dd><x-storefront.price :amount="$shipping" :currency="$currency" /></dd>
    </div>
    <div class="flex justify-between">
        <dt class="text-zinc-600 dark:text-zinc-400">Tax</dt>
        <dd><x-storefront.price :amount="$tax" :currency="$currency" /></dd>
    </div>
    <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-800">
        <dt>Total</dt>
        <dd><x-storefront.price :amount="$total" :currency="$currency" /></dd>
    </div>
</dl>
