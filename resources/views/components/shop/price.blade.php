@props(['amount', 'currency' => 'EUR'])

{{ \App\Services\Shop\Money::format((int) $amount, $currency) }}

