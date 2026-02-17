@props([
    'amount' => 0,
    'currency' => 'EUR',
    'compareAtAmount' => null,
])

@php
    $formattedPrice = number_format($amount / 100, 2, '.', ',') . ' ' . $currency;
    $hasDiscount = $compareAtAmount && $compareAtAmount > $amount;
    $formattedCompare = $hasDiscount ? number_format($compareAtAmount / 100, 2, '.', ',') . ' ' . $currency : null;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    @if($hasDiscount)
        <span class="text-red-600 dark:text-red-400">{{ $formattedPrice }}</span>
        <span class="text-sm text-gray-500 line-through dark:text-gray-400">{{ $formattedCompare }}</span>
    @else
        <span>{{ $formattedPrice }}</span>
    @endif
</span>
