@props([
    'amount',
    'currency' => 'USD',
])

@php
    $amount = (int) $amount;
    $currency = strtoupper((string) $currency);
    $currency = preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $currency : 'USD';
    $sign = $amount < 0 ? '-' : '';
    $formatted = $sign.number_format(abs($amount) / 100, 2, '.', ',').' '.$currency;
@endphp

<span {{ $attributes->class('whitespace-nowrap tabular-nums') }}>{{ $formatted }}</span>
