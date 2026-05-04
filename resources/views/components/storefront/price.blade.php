@props([
    'amount',
    'currency' => 'EUR',
    'compareAt' => null,
])

<span {{ $attributes->class('inline-flex flex-wrap items-baseline gap-2') }}>
    <span class="font-semibold text-zinc-950 dark:text-white">{{ \App\Support\Money::format((int) $amount, $currency) }}</span>

    @if ($compareAt && $compareAt > $amount)
        <span class="text-sm text-zinc-500 line-through dark:text-zinc-400">{{ \App\Support\Money::format((int) $compareAt, $currency) }}</span>
    @endif
</span>
