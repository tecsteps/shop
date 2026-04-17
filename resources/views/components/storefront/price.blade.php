@props([
    'amount' => 0,
    'currency' => 'USD',
    'compareAtAmount' => null,
])

@php
    $formatted = number_format($amount / 100, 2, '.', ',') . ' ' . strtoupper($currency);
    $hasCompare = $compareAtAmount !== null && $compareAtAmount > $amount;
    $formattedCompare = $hasCompare
        ? number_format($compareAtAmount / 100, 2, '.', ',') . ' ' . strtoupper($currency)
        : null;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    @if($hasCompare)
        <span class="text-red-600 dark:text-red-400 font-semibold">{{ $formatted }}</span>
        <span class="text-zinc-400 dark:text-zinc-500 line-through text-sm">{{ $formattedCompare }}</span>
    @else
        <span class="font-semibold">{{ $formatted }}</span>
    @endif
</span>
