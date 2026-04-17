@props(['product'])

@php
    $firstVariant = $product->variants?->first();
    $price = $firstVariant?->price_amount ?? null;
    $currency = $firstVariant?->currency ?? 'USD';
@endphp

<a href="/products/{{ $product->handle }}" class="group block overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
    <div class="aspect-square bg-gradient-to-br from-zinc-100 to-zinc-200 transition duration-500 group-hover:scale-105 dark:from-zinc-800 dark:to-zinc-900"></div>
    <div class="space-y-1 p-4">
        <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $product->title }}</h3>
        @if ($price !== null)
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                <x-storefront.price :amount="$price" :currency="$currency" />
            </p>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-500">View details</p>
        @endif
    </div>
</a>
