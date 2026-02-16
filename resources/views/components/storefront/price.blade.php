@props(['amount', 'currency' => 'USD', 'compareAtAmount' => null])

@php
    $formatted = number_format($amount / 100, 2, '.', ',') . ' ' . strtoupper($currency);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $formatted }}</span>
    @if($compareAtAmount && $compareAtAmount > $amount)
        @php
            $compareFormatted = number_format($compareAtAmount / 100, 2, '.', ',') . ' ' . strtoupper($currency);
        @endphp
        <span class="text-sm text-gray-500 line-through dark:text-gray-400">{{ $compareFormatted }}</span>
    @endif
</span>
