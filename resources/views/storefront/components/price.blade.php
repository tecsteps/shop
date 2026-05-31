@php
    use App\Support\Storefront\PriceFormatter;
@endphp

@props([
    'amount',
    'currency' => 'USD',
    'compareAtAmount' => null,
])

@php
    $onSale = $compareAtAmount !== null && (int) $compareAtAmount > (int) $amount;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2']) }}>
    <span class="font-semibold text-zinc-900 dark:text-white">
        {{ PriceFormatter::format((int) $amount, $currency) }}
    </span>

    @if ($onSale)
        <span class="text-zinc-500 line-through dark:text-zinc-400">
            {{ PriceFormatter::format((int) $compareAtAmount, $currency) }}
        </span>
        <x-storefront::badge text="Sale" variant="sale" />
    @endif
</span>
