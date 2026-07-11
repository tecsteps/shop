@props(['amount', 'currency' => 'USD', 'compareAtAmount' => null])
<span {{ $attributes->class(['inline-flex items-baseline gap-2']) }}>
    <span class="font-semibold">{{ \Illuminate\Support\Number::currency($amount / 100, in: $currency) }}</span>
    @if ($compareAtAmount !== null && $compareAtAmount > $amount)
        <span class="text-sm text-zinc-500 line-through">{{ \Illuminate\Support\Number::currency($compareAtAmount / 100, in: $currency) }}</span>
        <span class="sr-only">Sale price</span>
    @endif
</span>
