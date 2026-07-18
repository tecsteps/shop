@props([
    'amount' => 0,
    'currency' => 'EUR',
    'compareAtAmount' => null,
])

@php
    $onSale = $compareAtAmount !== null && $compareAtAmount > $amount;
@endphp

<span {{ $attributes->class(['inline-flex items-baseline gap-2']) }}>
    <span class="font-semibold text-zinc-900 dark:text-white">
        {{ \App\Support\Money::format($amount, $currency) }}
    </span>

    @if ($onSale)
        <span class="text-sm text-zinc-500 line-through dark:text-zinc-400">
            {{ \App\Support\Money::format($compareAtAmount, $currency) }}
        </span>

        <x-storefront.badge text="Sale" variant="sale" />
    @endif
</span>
