{{--
    Formatted price with optional compare-at price (spec 04 §16).

    Props:
    - amount: int (required) — price in minor units (cents)
    - currency: string — ISO 4217 code (default: USD)
    - compareAtAmount: int|null — original price in minor units for sale display
--}}
@props([
    'amount',
    'currency' => 'USD',
    'compareAtAmount' => null,
])

@php
    $onSale = $compareAtAmount !== null && (int) $compareAtAmount > (int) $amount;
@endphp

<span {{ $attributes->class('inline-flex flex-wrap items-center gap-x-2 gap-y-1') }}>
    <span class="font-semibold">{{ \App\Support\Money::format((int) $amount, $currency) }}</span>
    @if ($onSale)
        <s class="text-gray-500 dark:text-gray-400">{{ \App\Support\Money::format((int) $compareAtAmount, $currency) }}</s>
        <x-storefront::badge text="Sale" variant="sale" />
    @endif
</span>
