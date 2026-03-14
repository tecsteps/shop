@props([
    'amount' => 0,
    'currency' => 'EUR',
    'compareAt' => null,
])

@php
    $formatPrice = function (int $cents, string $currency): string {
        $value = $cents / 100;
        $formatted = number_format(abs($value), 2, '.', ',');
        if ($cents < 0) {
            $formatted = '-' . $formatted;
        }
        return $formatted . ' ' . $currency;
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    @if ($compareAt && $compareAt > $amount)
        <span class="text-red-600 dark:text-red-400 font-semibold">{{ $formatPrice($amount, $currency) }}</span>
        <span class="text-zinc-400 dark:text-zinc-500 line-through text-sm">{{ $formatPrice($compareAt, $currency) }}</span>
    @else
        <span class="font-semibold">{{ $formatPrice($amount, $currency) }}</span>
    @endif
</span>
