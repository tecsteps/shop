@props([
    'amount' => 0,
    'currency' => null,
    'compareAt' => null,
])

@php
    $currency = $currency ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
    $format = fn (int $cents): string => number_format($cents / 100, 2, '.', ',').' '.$currency;
@endphp

<span {{ $attributes->class(['inline-flex items-baseline gap-2']) }}>
    <span class="font-semibold">{{ $format((int) $amount) }}</span>
    @if ($compareAt && (int) $compareAt > (int) $amount)
        <span class="text-sm text-zinc-500 line-through dark:text-zinc-400">{{ $format((int) $compareAt) }}</span>
    @endif
</span>
