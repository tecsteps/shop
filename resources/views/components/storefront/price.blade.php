@props([
    'amount',
    'currency' => null,
    'compareAtAmount' => null,
])

@php
    $currency ??= $currentStore->default_currency ?? 'EUR';
    $isOnSale = $compareAtAmount !== null && $compareAtAmount > $amount;
@endphp

<span {{ $attributes->class('inline-flex flex-wrap items-baseline gap-x-2') }}>
    <span class="font-semibold text-zinc-900 dark:text-white">
        {{ \App\Support\Storefront\PriceFormatter::format($amount, $currency) }}
    </span>
    @if ($isOnSale)
        <s class="text-sm font-normal text-zinc-500 dark:text-zinc-400">
            <span class="sr-only">{{ __('Original price:') }}</span>
            {{ \App\Support\Storefront\PriceFormatter::format($compareAtAmount, $currency) }}
        </s>
    @endif
</span>
