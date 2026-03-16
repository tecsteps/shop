@props([
    'amount' => 0,
    'currency' => 'EUR',
    'class' => '',
])

@php
    $value = $amount / 100;
    $formatted = number_format(abs($value), 2, '.', ',');
    $display = ($value < 0 ? '-' : '') . $formatted . ' ' . $currency;
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $display }}</span>
