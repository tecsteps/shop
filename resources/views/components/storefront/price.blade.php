@props(['amount', 'currency' => 'EUR', 'compareAtAmount' => null])

@php
    $formatted = number_format($amount / 100, 2, '.', ',') . ' ' . $currency;
    $hasCompare = $compareAtAmount && $compareAtAmount > $amount;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    @if($hasCompare)
        <span class="text-red-600 font-semibold dark:text-red-400">{{ $formatted }}</span>
        <span class="text-sm text-gray-400 line-through dark:text-gray-500">
            {{ number_format($compareAtAmount / 100, 2, '.', ',') }} {{ $currency }}
        </span>
        <x-storefront.badge text="Sale" variant="sale" />
    @else
        <span class="font-semibold text-gray-900 dark:text-white">{{ $formatted }}</span>
    @endif
</span>
