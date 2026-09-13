@props(['amount', 'currency', 'compareAtAmount' => null])

@php
    $formatted = \App\Support\Money::format((int) $amount, (string) $currency);
    $onSale = $compareAtAmount !== null && (int) $compareAtAmount > (int) $amount;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2']) }}>
    <span class="font-semibold">{{ $formatted }}</span>
    @if ($onSale)
        <span class="text-sm text-zinc-500 line-through dark:text-zinc-400">{{ \App\Support\Money::format((int) $compareAtAmount, (string) $currency) }}</span>
        <x-storefront.badge text="Sale" variant="sale" />
    @endif
</span>
