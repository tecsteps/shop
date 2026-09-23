
@php
    $productTitle = data_get($product, 'title', 'Product');
    $productDescription = data_get($product, 'description_html', data_get($product, 'description', ''));
    $productMedia = data_get($product, 'media', data_get($product, 'images', []));
    $productPrice = (int) data_get($product, 'price_amount', data_get($product, 'price', 0));
    $productComparePrice = data_get($product, 'compare_at_amount');
    $productCurrency = data_get($product, 'currency', data_get($currentStore ?? null, 'currency', 'EUR'));
    $productVariants = data_get($product, 'variants', []);
    $productOptions = data_get($product, 'options', []);
    $productImage = data_get($productMedia, '0.url', data_get($productMedia, '0.src'));
    $productImageUrl = $productImage ? url($productImage) : null;
    $selectedImageIndex = isset($selectedImageIndex) ? (int) $selectedImageIndex : 0;
    $activeImage = data_get($productMedia, $selectedImageIndex.'.url', data_get($productMedia, $selectedImageIndex.'.src', $productImage));
    $activeImageAlt = data_get($productMedia, $selectedImageIndex.'.alt', $productTitle);
    $productBreadcrumbs = [['label' => 'Home', 'url' => '/']];
    if (data_get($product, 'collection.title')) {
        $productBreadcrumbs[] = ['label' => data_get($product, 'collection.title'), 'url' => url('/collections/'.data_get($product, 'collection.handle'))];
    }
    $productBreadcrumbs[] = ['label' => $productTitle];
@endphp

@section('title', $productTitle)
@section('meta_description', \Illuminate\Support\Str::limit(trim(strip_tags($productDescription)), 160))
@section('social_meta')
    <meta property="og:type" content="product"><meta property="og:title" content="{{ $productTitle }}"><meta property="og:description" content="{{ \Illuminate\Support\Str::limit(trim(strip_tags($productDescription)), 160) }}"><meta property="og:image" content="{{ $productImageUrl }}"><meta property="og:url" content="{{ url()->current() }}"><meta property="product:price:amount" content="{{ number_format($productPrice / 100, 2, '.', '') }}"><meta property="product:price:currency" content="{{ $productCurrency }}">
@endsection
@section('structured_data')
    <script type="application/ld+json">{!! json_encode([chr(64).'context' => 'https://schema.org', chr(64).'type' => 'Product', 'name' => $productTitle, 'description' => trim(strip_tags($productDescription)), 'image' => $productImageUrl ? [$productImageUrl] : [], 'sku' => data_get($product, 'sku'), 'offers' => [chr(64).'type' => 'Offer', 'priceCurrency' => $productCurrency, 'price' => number_format($productPrice / 100, 2, '.', ''), 'availability' => data_get($product, 'sold_out') ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock', 'url' => url()->current()]], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

<div>
<article class="mx-auto max-w-7xl px-4 pb-14 pt-8 sm:px-6 sm:pb-20 sm:pt-10 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => $productBreadcrumbs])
        <div class="mt-7 grid gap-9 lg:grid-cols-2 lg:items-start lg:gap-14">
            <section aria-label="Product images" class="lg:sticky lg:top-28">
                <div class="relative hidden aspect-square overflow-hidden rounded-3xl bg-stone-100 dark:bg-stone-900 lg:block">
                    @if ($activeImage)<img src="{{ $activeImage }}" alt="{{ $activeImageAlt }}" class="size-full object-cover" fetchpriority="high">@else<span class="flex size-full items-center justify-center text-stone-300 dark:text-stone-700">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-20'])</span>@endif
                </div>
                @if (count($productMedia) > 1)
                    <div class="flex snap-x gap-3 overflow-x-auto pb-1 lg:hidden" aria-label="Product image gallery">
                        @foreach ($productMedia as $index => $media)
                            <div wire:key="mobile-product-image-{{ data_get($media, 'id', $index) }}" class="aspect-square w-full flex-none snap-center overflow-hidden rounded-3xl bg-stone-100 dark:bg-stone-900"><img src="{{ data_get($media, 'url', data_get($media, 'src')) }}" alt="{{ data_get($media, 'alt', $productTitle.' image '.($index + 1)) }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" class="size-full object-cover"></div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex justify-center gap-2 lg:hidden" aria-label="Choose product image">
                        @foreach ($productMedia as $index => $media)<button type="button" wire:click="selectImage({{ $index }})" aria-label="Show image {{ $index + 1 }} of {{ count($productMedia) }}" aria-pressed="{{ $index === $selectedImageIndex ? 'true' : 'false' }}" class="size-3 rounded-full {{ $index === $selectedImageIndex ? 'bg-stone-950 dark:bg-white' : 'bg-stone-300 dark:bg-stone-700' }}"><span class="sr-only">Image {{ $index + 1 }}</span></button>@endforeach
                    </div>
                    <div class="mt-3 hidden snap-x gap-3 overflow-x-auto pb-1 lg:grid lg:grid-cols-6" aria-label="More product images">
                        @foreach ($productMedia as $index => $media)
                            <button type="button" wire:click="selectImage({{ $index }})" wire:key="product-image-{{ data_get($media, 'id', $index) }}" aria-label="View image {{ $index + 1 }} of {{ count($productMedia) }}" aria-pressed="{{ $index === $selectedImageIndex ? 'true' : 'false' }}" class="size-16 shrink-0 snap-start overflow-hidden rounded-xl border-2 {{ $index === $selectedImageIndex ? 'border-blue-700 dark:border-blue-400' : 'border-transparent' }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 lg:size-auto lg:aspect-square">
                                <img src="{{ data_get($media, 'thumbnail_url', data_get($media, 'url', data_get($media, 'src'))) }}" alt="{{ data_get($media, 'alt', $productTitle.' image '.($index + 1)) }}" loading="lazy" class="size-full object-cover">
                            </button>
                        @endforeach
                    </div>
                    <p class="sr-only" aria-live="polite">Image {{ $selectedImageIndex + 1 }} of {{ count($productMedia) }}</p>
                @elseif ($productImage)
                    <div class="aspect-square overflow-hidden rounded-3xl bg-stone-100 dark:bg-stone-900 lg:hidden"><img src="{{ $productImage }}" alt="{{ data_get($productMedia, '0.alt', $productTitle) }}" fetchpriority="high" class="size-full object-cover"></div>
                @endif
            </section>

            <div class="min-w-0 lg:pt-2">
                @if (data_get($product, 'vendor'))<p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">{{ data_get($product, 'vendor') }}</p>@endif
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $productTitle }}</h1>
                <div aria-live="polite" class="mt-4 flex flex-wrap items-center gap-3">
                    @include('storefront.components.price', ['amount' => $productPrice, 'currency' => $productCurrency, 'compareAtAmount' => $productComparePrice, 'live' => true])
                    @if ($productComparePrice && $productComparePrice > $productPrice)@include('storefront.components.badge', ['text' => 'Sale', 'variant' => 'sale'])@endif
                </div>

                @if (count($productOptions))
                    <div class="mt-7 grid gap-5">
                        @foreach ($productOptions as $optionIndex => $option)
                            @php
                                $optionName = data_get($option, 'name', 'Option');
                                $optionValues = data_get($option, 'values', []);
                            @endphp
                            <fieldset wire:key="product-option-{{ $optionIndex }}">
                                <legend class="mb-2.5 text-sm font-semibold">{{ $optionName }} <span class="font-normal text-stone-600 dark:text-stone-300">{{ data_get($selectedOptions ?? [], $optionName) }}</span></legend>
                                @if (strtolower($optionName) === 'color')
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($optionValues as $value)
                                            @php
                                                $colorValue = strtolower((string) $value);
                                                $swatch = ['black' => '#111111', 'white' => '#ffffff', 'navy' => '#1e3a5f', 'blue' => '#2563eb', 'red' => '#dc2626', 'green' => '#15803d', 'beige' => '#d6c2a1'][$colorValue] ?? '#9ca3af';
                                            @endphp
                                            <label class="relative inline-flex cursor-pointer items-center"><input type="radio" name="option-{{ $optionIndex }}" value="{{ $value }}" wire:model.live="selectedOptions.{{ $optionName }}" class="peer sr-only"><span class="size-9 rounded-full border border-stone-300 shadow-sm peer-checked:ring-2 peer-checked:ring-blue-700 peer-checked:ring-offset-2 peer-focus-visible:outline-2 peer-focus-visible:outline-blue-700 dark:border-stone-600 peer-checked:dark:ring-blue-400" style="background-color: {{ $swatch }}" title="{{ $value }}"></span><span class="sr-only">{{ $value }}</span></label>
                                        @endforeach
                                    </div>
                                @elseif (count($optionValues) <= 6)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($optionValues as $value)
                                            <label class="cursor-pointer"><input type="radio" name="option-{{ $optionIndex }}" value="{{ $value }}" wire:model.live="selectedOptions.{{ $optionName }}" class="peer sr-only"><span class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-stone-300 px-4 text-sm font-medium peer-checked:border-blue-700 peer-checked:bg-blue-50 peer-checked:text-blue-900 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-blue-700 dark:border-stone-700 dark:peer-checked:border-blue-400 dark:peer-checked:bg-blue-950 dark:peer-checked:text-blue-100">{{ $value }}</span></label>
                                        @endforeach
                                    </div>
                                @else
                                    <label for="product-option-{{ $optionIndex }}" class="sr-only">{{ $optionName }}</label><select id="product-option-{{ $optionIndex }}" wire:model.live="selectedOptions.{{ $optionName }}" class="storefront-input">@foreach ($optionValues as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select>
                                @endif
                            </fieldset>
                        @endforeach
                    </div>
                @endif

                @php
                    $inventory = data_get($selectedVariant ?? null, 'inventory_quantity', data_get($product, 'inventory_quantity', null));
                    $inventoryPolicy = data_get($selectedVariant ?? null, 'inventory_policy', data_get($product, 'inventory_policy', 'deny'));
                    $stockLabel = $inventory === null || $inventory > 10 ? 'In stock' : ($inventory > 0 ? 'Only '.$inventory.' left in stock' : ($inventoryPolicy === 'continue' ? 'Available on backorder' : 'Out of stock'));
                    $isAvailable = ($inventory === null || $inventory > 0 || $inventoryPolicy === 'continue') && !data_get($product, 'sold_out', false);
                @endphp
                <p aria-live="polite" class="mt-6 inline-flex items-center gap-2 text-sm {{ $isAvailable ? 'text-emerald-800 dark:text-emerald-300' : 'text-rose-800 dark:text-rose-300' }}">
                    @include('storefront.components.icon', ['name' => $isAvailable ? 'check' : 'alert', 'class' => 'size-4']) {{ $stockLabel }}
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    @include('storefront.components.quantity-selector', ['value' => $quantity ?? 1, 'min' => 1, 'max' => $inventoryPolicy === 'continue' ? null : $inventory, 'wireModel' => 'quantity', 'decreaseAction' => 'decreaseQuantity', 'increaseAction' => 'increaseQuantity'])
                    <span class="text-sm text-stone-600 dark:text-stone-300">Quantity</span>
                </div>
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" @disabled(!$isAvailable) class="mt-5 inline-flex min-h-14 w-full items-center justify-center rounded-2xl bg-blue-700 px-6 text-base font-semibold text-white transition hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:cursor-not-allowed disabled:bg-stone-300 disabled:text-stone-600 dark:bg-blue-400 dark:text-stone-950 dark:hover:bg-blue-300 dark:disabled:bg-stone-800 dark:disabled:text-stone-500">
                    <span wire:loading.remove wire:target="addToCart">{{ $isAvailable ? 'Add to cart' : 'Sold out' }}</span><span wire:loading wire:target="addToCart">Adding…</span>
                </button>
                @error('variant')<p class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror

                @if ($productDescription)
                    <div class="storefront-prose mt-8 border-t border-stone-200 pt-7 dark:border-stone-800">{!! $productDescription !!}</div>
                @endif
                @if (count(data_get($product, 'tags', [])))
                    <ul class="mt-6 flex flex-wrap gap-2" aria-label="Product tags">@foreach (data_get($product, 'tags', []) as $tag)<li class="rounded-full bg-stone-100 px-3 py-1.5 text-xs text-stone-600 dark:bg-stone-900 dark:text-stone-300">{{ data_get($tag, 'name', $tag) }}</li>@endforeach</ul>
                @endif
            </div>
        </div>
    </article>
</div>
