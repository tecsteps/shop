@props([
    'amount' => 0,
    'currency' => 'USD',
])

@php
    $amountCents = is_numeric($amount) ? (int) $amount : 0;
    $amountFormatted = number_format($amountCents / 100, 2);
    $symbols = [
        'USD' => '$',
        'EUR' => 'EUR ',
        'GBP' => 'GBP ',
        'JPY' => 'JPY ',
        'CHF' => 'CHF ',
    ];
    $currencyUpper = strtoupper((string) $currency);
    $symbol = $symbols[$currencyUpper] ?? ($currencyUpper.' ');
@endphp

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ $symbol }}{{ $amountFormatted }}</span>
