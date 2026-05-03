@props(['amount', 'currency'])

{{ number_format(((int) $amount) / 100, 2, '.', ',') }} {{ $currency }}
