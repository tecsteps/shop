@props(['amount' => 0, 'currency' => 'USD'])

@php
    $value = number_format($amount / 100, 2, '.', ',');
@endphp

<span {{ $attributes->merge(['class' => 'whitespace-nowrap']) }}>{{ $value }} {{ $currency }}</span>
