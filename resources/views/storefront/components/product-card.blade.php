@php
    $productTitle = data_get($product, 'title', 'Product');
    $productHandle = data_get($product, 'handle', data_get($product, 'slug', ''));
    $media = data_get($product, 'media', data_get($product, 'images', []));
    $primaryImage = data_get($media, '0.url', data_get($media, '0.src', data_get($product, 'image_url')));
    $secondaryImage = data_get($media, '1.url', data_get($media, '1.src'));
    $priceAmount = (int) data_get($product, 'price_amount', data_get($product, 'price', 0));
    $compareAtAmount = data_get($product, 'compare_at_amount');
    $productCurrency = data_get($product, 'currency', data_get($currentStore ?? null, 'currency', 'EUR'));
    $isSoldOut = (bool) data_get($product, 'sold_out', false);
    $variants = data_get($product, 'variants', []);
    $variantCount = is_countable($variants) ? count($variants) : 0;
    $firstVariantId = data_get($variants, '0.id');
    $headingLevel = in_array($headingLevel ?? 'h3', ['h2', 'h3', 'h4'], true) ? ($headingLevel ?? 'h3') : 'h3';
@endphp
<article class="group min-w-0">
    <div class="relative overflow-hidden rounded-2xl bg-stone-100 dark:bg-stone-900">
        <a href="{{ url('/products/'.$productHandle) }}" class="relative block aspect-square focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700" aria-label="View {{ $productTitle }}">
            @if ($primaryImage)
                <img src="{{ $primaryImage }}" alt="{{ data_get($media, '0.alt', $productTitle) }}" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-[1.025] {{ $secondaryImage ? 'group-hover:opacity-0' : '' }}">
                @if ($secondaryImage)
                    <img src="{{ $secondaryImage }}" alt="{{ data_get($media, '1.alt', $productTitle.' alternate view') }}" loading="lazy" class="absolute inset-0 size-full object-cover opacity-0 transition duration-500 group-hover:opacity-100 motion-reduce:transition-none">
                @endif
            @else
                <span class="flex size-full items-center justify-center text-stone-300 dark:text-stone-700" aria-label="No image available for {{ $productTitle }}">
                    @include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-14'])
                </span>
            @endif
        </a>
        <div class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
            @if ($compareAtAmount && $compareAtAmount > $priceAmount)
                @include('storefront.components.badge', ['text' => 'Sale', 'variant' => 'sale'])
            @endif
            @if ($isSoldOut)
                @include('storefront.components.badge', ['text' => 'Sold out', 'variant' => 'sold-out'])
            @endif
        </div>
    </div>
    <div class="pt-3">
        <{{ $headingLevel }} class="line-clamp-2 min-h-10 text-sm font-medium leading-5 text-stone-900 dark:text-stone-100">
            <a href="{{ url('/products/'.$productHandle) }}" class="rounded-sm underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-blue-700">{{ $productTitle }}</a>
        </{{ $headingLevel }}>
        <div class="mt-1.5 text-sm">
            @include('storefront.components.price', ['amount' => $priceAmount, 'currency' => $productCurrency, 'compareAtAmount' => $compareAtAmount])
        </div>
        @if (($showQuickAdd ?? true) && !$isSoldOut)
            <div class="mt-3 opacity-100 transition [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 [@media(hover:hover)]:group-focus-within:opacity-100">
                @if ($variantCount > 1 || !$firstVariantId)
                    <a href="{{ url('/products/'.$productHandle) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-stone-300 px-3 text-sm font-semibold text-stone-800 hover:border-stone-900 hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:border-stone-700 dark:text-stone-100 dark:hover:border-stone-400 dark:hover:bg-stone-800">Choose options</a>
                @else
                    <button type="button" wire:click="quickAdd({{ $firstVariantId }})" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-stone-950 px-3 text-sm font-semibold text-white transition hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-white dark:text-stone-950 dark:hover:bg-blue-200">
                        <span wire:loading.remove wire:target="quickAdd({{ $firstVariantId }})">Add to cart</span>
                        <span wire:loading wire:target="quickAdd({{ $firstVariantId }})">Adding…</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</article>
