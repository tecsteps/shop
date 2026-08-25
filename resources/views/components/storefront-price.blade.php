@props([
    'amount' => 0,
    'currency' => null,
    'compareAtAmount' => null,
])

@php
    $storeCurrency = app()->bound('current_store') ? app('current_store')->default_currency : 'EUR';
    $currency = $currency ?? $storeCurrency;
    $amount = (int) $amount;
    $formatted = number_format($amount / 100, 2, '.', ',').' '.$currency;
    $hasCompare = $compareAtAmount !== null && (int) $compareAtAmount > $amount;
    $compareFormatted = $hasCompare
        ? number_format((int) $compareAtAmount / 100, 2, '.', ',').' '.$currency
        : null;
@endphp

<span class="inline-flex flex-wrap items-center gap-x-2 gap-y-1 {{ $attributes->get('class') }}">
    <span class="font-semibold text-zinc-900 dark:text-white">{{ $formatted }}</span>

    @if ($hasCompare)
        <s class="text-sm text-zinc-400 dark:text-zinc-500" aria-label="Compare at {{ $compareFormatted }}">{{ $compareFormatted }}</s>
        <x-storefront-badge variant="sale" text="Sale" />
    @endif
</span>
