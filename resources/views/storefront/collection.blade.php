
@php
    $collectionTitle = data_get($collection, 'title', 'Collection');
    $collectionDescription = data_get($collection, 'description', '');
    $productsCount = method_exists($products, 'total') ? $products->total() : (is_countable($products) ? count($products) : 0);
    $productTypes = data_get($collection, 'product_types') ?? [];
    $vendors = data_get($collection, 'vendors') ?? [];
@endphp

@section('title', $collectionTitle)
@section('meta_description', \Illuminate\Support\Str::limit(trim(strip_tags($collectionDescription)), 160))

<div>
<section x-data="{ filtersOpen: false }" x-on:keydown.escape.window="filtersOpen = false" class="mx-auto max-w-7xl px-4 pb-12 pt-8 sm:px-6 sm:pb-16 sm:pt-10 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => 'Collections', 'url' => '/collections'], ['label' => $collectionTitle]]])
        <div class="mt-7 max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ $collectionTitle }}</h1>
            @if ($collectionDescription)<div class="storefront-prose mt-4 line-clamp-4">{!! $collectionDescription !!}</div>@endif
        </div>

        <div class="mt-9 flex flex-wrap items-center justify-between gap-3 border-y border-stone-200 py-3 dark:border-stone-800">
            <button type="button" x-on:click="filtersOpen = true" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-medium hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 lg:hidden dark:hover:bg-stone-900">@include('storefront.components.icon', ['name' => 'menu', 'class' => 'size-4']) Filters</button>
            <span class="hidden text-sm text-stone-600 dark:text-stone-300 sm:inline" aria-live="polite">{{ $productsCount }} {{ \Illuminate\Support\Str::plural('product', $productsCount) }}</span>
            <div class="ml-auto flex items-center gap-2">
                <label for="collection-sort" class="text-sm text-stone-600 dark:text-stone-300">Sort by</label>
                <select id="collection-sort" wire:model.live="sort" class="min-h-11 max-w-[11rem] rounded-xl border border-stone-300 bg-white px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-blue-700 dark:border-stone-700 dark:bg-stone-900">
                    <option value="featured">Featured</option><option value="price_asc">Price: Low to High</option><option value="price_desc">Price: High to Low</option><option value="newest">Newest</option><option value="best_selling">Best Selling</option>
                </select>
            </div>
        </div>

        <div x-show="filtersOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 lg:hidden" role="presentation">
            <button type="button" x-on:click="filtersOpen = false" class="absolute inset-0 bg-stone-950/50" aria-label="Close filters"></button>
            <aside role="dialog" aria-modal="true" aria-labelledby="mobile-filters-heading" class="absolute inset-y-0 left-0 flex w-[min(23rem,88vw)] flex-col bg-white shadow-2xl dark:bg-stone-950">
                <div class="flex min-h-16 items-center justify-between gap-3 border-b border-stone-200 px-5 dark:border-stone-800"><h2 id="mobile-filters-heading" class="text-lg font-semibold">Filters</h2><div class="flex items-center gap-2"><button type="button" wire:click="clearFilters" class="min-h-10 px-2 text-xs font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Clear all</button><button type="button" x-on:click="filtersOpen = false" class="inline-flex size-11 items-center justify-center rounded-full hover:bg-stone-100 dark:hover:bg-stone-900" aria-label="Close filters">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-5'])</button></div></div>
                <div class="flex-1 space-y-5 overflow-y-auto p-5"><fieldset><legend class="text-sm font-semibold">Availability</legend><label class="mt-2 flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" wire:model.live="inStock" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">In stock</label></fieldset><fieldset class="border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Price range</legend><div class="mt-3">@include('storefront.components.price-filter', ['idPrefix' => 'mobile-collection-filter-price'])</div></fieldset>
                    @if (count($productTypes))<fieldset class="border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Product type</legend><div class="mt-2 grid gap-1">@foreach ($productTypes as $type)<label class="flex min-h-10 items-center gap-3 text-sm"><input type="checkbox" value="{{ data_get($type, 'name', $type) }}" wire:model.live="selectedTypes" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">{{ data_get($type, 'name', $type) }}</label>@endforeach</div></fieldset>@endif
                    @if (count($vendors))<fieldset class="border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Vendor</legend><div class="mt-2 grid gap-1">@foreach ($vendors as $vendor)<label class="flex min-h-10 items-center gap-3 text-sm"><input type="checkbox" value="{{ data_get($vendor, 'name', $vendor) }}" wire:model.live="selectedVendors" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">{{ data_get($vendor, 'name', $vendor) }}</label>@endforeach</div></fieldset>@endif
                </div>
                <div class="border-t border-stone-200 p-4 dark:border-stone-800"><button type="button" x-on:click="filtersOpen = false" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-5 text-sm font-semibold text-white dark:bg-blue-400 dark:text-stone-950">Apply filters</button></div>
            </aside>
        </div>

        <div class="mt-6 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-9">
            <aside aria-label="Product filters" class="mb-8 hidden lg:block">
                <div class="sticky top-28 rounded-2xl border border-stone-200 p-5 dark:border-stone-800">
                    <div class="flex items-center justify-between"><h2 class="font-semibold">Filters</h2><button type="button" wire:click="clearFilters" class="text-xs font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Clear all</button></div>
                    <fieldset class="mt-5 border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Availability</legend><label class="mt-3 flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" wire:model.live="inStock" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">In stock</label></fieldset>
                    <fieldset class="mt-4 border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Price range</legend><div class="mt-3">@include('storefront.components.price-filter', ['idPrefix' => 'collection-filter-price'])</div></fieldset>
                    @if (count($productTypes))<fieldset class="mt-4 border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Product type</legend><div class="mt-2 grid gap-1">@foreach ($productTypes as $type)<label class="flex min-h-10 items-center gap-3 text-sm"><input type="checkbox" value="{{ data_get($type, 'name', $type) }}" wire:model.live="selectedTypes" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">{{ data_get($type, 'name', $type) }}</label>@endforeach</div></fieldset>@endif
                    @if (count($vendors))<fieldset class="mt-4 border-t border-stone-200 pt-4 dark:border-stone-800"><legend class="text-sm font-semibold">Vendor</legend><div class="mt-2 grid gap-1">@foreach ($vendors as $vendor)<label class="flex min-h-10 items-center gap-3 text-sm"><input type="checkbox" value="{{ data_get($vendor, 'name', $vendor) }}" wire:model.live="selectedVendors" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">{{ data_get($vendor, 'name', $vendor) }}</label>@endforeach</div></fieldset>@endif
                </div>
            </aside>

            <div class="min-w-0" wire:loading.class="opacity-60" wire:target="sort,inStock,minPrice,maxPrice,selectedTypes,selectedVendors">
                @if (!empty($activeFilters ?? []))<div class="mb-5 flex flex-wrap items-center gap-2" aria-label="Active filters">@foreach ($activeFilters as $filter)<span class="inline-flex min-h-9 items-center gap-2 rounded-full bg-stone-100 px-3 text-xs font-medium dark:bg-stone-800">{{ data_get($filter, 'label', $filter) }}<button type="button" wire:click="removeFilter('{{ data_get($filter, 'key', $filter) }}')" class="inline-flex size-6 items-center justify-center rounded-full hover:bg-stone-200 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-700" aria-label="Remove filter {{ data_get($filter, 'label', $filter) }}">×</button></span>@endforeach<button type="button" wire:click="clearFilters" class="min-h-9 px-2 text-xs font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Clear all</button></div>@endif
                @if ($productsCount > 0)
                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-5 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <div wire:key="collection-product-{{ data_get($product, 'id', data_get($product, 'handle', $loop->index)) }}">@include('storefront.components.product-card', ['product' => $product])</div>
                        @endforeach
                    </div>
                    @include('storefront.components.pagination', ['paginator' => $products])
                @else
                    <div class="flex min-h-80 flex-col items-center justify-center rounded-3xl border border-dashed border-stone-300 px-6 text-center dark:border-stone-700">
                        <span class="flex size-14 items-center justify-center rounded-full bg-stone-100 text-stone-400 dark:bg-stone-900">@include('storefront.components.icon', ['name' => 'search', 'class' => 'size-7'])</span>
                        <h2 class="mt-4 text-lg font-semibold">No products found</h2><p class="mt-2 max-w-sm text-sm leading-6 text-stone-600 dark:text-stone-300">Try adjusting your filters or browse the full collection.</p><button type="button" wire:click="clearFilters" class="mt-5 inline-flex min-h-11 items-center rounded-xl border border-stone-300 px-5 text-sm font-semibold hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-900">Clear filters</button>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
