@props([
    'amount',
    'compareAt' => null,
    'currency' => 'EUR',
    'size' => 'sm',
])

@php
    $formatPrice = function (int $amount, string $currency): string {
        $value = $amount / 100;
        return number_format($value, 2, '.', ',') . ' ' . $currency;
    };
    $isOnSale = $compareAt && $compareAt > $amount;
    $textSize = match($size) {
        'lg' => 'text-xl font-bold',
        'md' => 'text-base font-semibold',
        default => 'text-sm font-semibold',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="{{ $textSize }} {{ $isOnSale ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
        {{ $formatPrice($amount, $currency) }}
    </span>
    @if($isOnSale)
        <span class="text-sm text-zinc-400 line-through dark:text-zinc-500">
            {{ $formatPrice($compareAt, $currency) }}
        </span>
    @endif
</span>
