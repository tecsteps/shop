@props([
    'product',
])

@php
    $handle = data_get($product, 'handle');
    $title = data_get($product, 'title');
    $priceAmount = data_get($product, 'price_amount');
    $compareAt = data_get($product, 'compare_at_amount');
    $currency = data_get($product, 'currency')
        ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
    $image = data_get($product, 'image_url');
    $href = $handle ? route('storefront.products.show', $handle) : '#';
@endphp

<a href="{{ $href }}" {{ $attributes->class(['group block overflow-hidden rounded-lg border border-zinc-200 bg-white transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900']) }} data-testid="product-card">
    <div class="aspect-square w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-[1.02]" />
        @else
            <div class="flex h-full w-full items-center justify-center text-zinc-400">
                <flux:icon name="photo" class="size-10" />
            </div>
        @endif
    </div>
    <div class="space-y-1 p-4">
        <div class="text-sm font-medium text-zinc-900 group-hover:underline dark:text-zinc-100">{{ $title }}</div>
        @if ($priceAmount !== null)
            <x-storefront.price :amount="$priceAmount" :compare-at="$compareAt" :currency="$currency" class="text-sm" />
        @endif
    </div>
</a>
