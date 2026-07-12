<div class="sf-container sf-page-y" x-data="{ mobileFilters: @entangle('filtersOpen').live }">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => url('/')], ['label' => 'Collections', 'url' => url('/collections')], ['label' => $collection->title]]" />
    <header class="mt-7 max-w-3xl">
        <p class="sf-eyebrow">Collection</p>
        <h1 class="sf-page-title">{{ $collection->title }}</h1>
        @if ($collection->description_html)
            <x-storefront.rich-text :html="$collection->description_html" class="mt-5" />
        @endif
    </header>

    <div class="mt-10 flex items-center justify-between gap-4 border-y border-slate-200 py-4 dark:border-slate-800">
        <button type="button" class="sf-button sf-button-secondary lg:hidden" @click="mobileFilters = true" aria-controls="collection-filters" :aria-expanded="mobileFilters">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2.75 3.5a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H2.75ZM5.75 9.25a.75.75 0 0 0 0 1.5h8.5a.75.75 0 0 0 0-1.5h-8.5ZM8.75 15a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z"/></svg>
            Filters
        </button>
        <p class="hidden text-sm text-slate-500 sm:block" aria-live="polite">{{ $products->total() }} {{ str('product')->plural($products->total()) }}</p>
        <label class="ml-auto flex items-center gap-3 text-sm font-medium">
            <span class="hidden sm:inline">Sort by</span>
            <select wire:model.live="sort" class="sf-select min-w-44" aria-label="Sort products">
                <option value="featured">Featured</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="newest">Newest</option>
                <option value="best_selling">Best Selling</option>
            </select>
        </label>
    </div>

    @if ($this->hasActiveFilters)
        <div class="mt-5 flex flex-wrap items-center gap-2" aria-label="Active filters">
            @if ($inStock)<button wire:click="$set('inStock', false)" class="sf-filter-pill">In stock <span aria-hidden="true">&times;</span></button>@endif
            @if ($minPrice !== '')<button wire:click="$set('minPrice', '')" class="sf-filter-pill">From {{ $minPrice }} {{ $currentStore->default_currency }} <span aria-hidden="true">&times;</span></button>@endif
            @if ($maxPrice !== '')<button wire:click="$set('maxPrice', '')" class="sf-filter-pill">To {{ $maxPrice }} {{ $currentStore->default_currency }} <span aria-hidden="true">&times;</span></button>@endif
            @foreach ($types as $type)<button wire:click="$set('types', {{ Js::from(array_values(array_diff($types, [$type]))) }})" class="sf-filter-pill">{{ $type }} <span aria-hidden="true">&times;</span></button>@endforeach
            @foreach ($vendors as $vendor)<button wire:click="$set('vendors', {{ Js::from(array_values(array_diff($vendors, [$vendor]))) }})" class="sf-filter-pill">{{ $vendor }} <span aria-hidden="true">&times;</span></button>@endforeach
            <button wire:click="clearFilters" class="sf-text-link ml-1 text-sm">Clear all</button>
        </div>
    @endif

    <div class="mt-8 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-9">
        <div x-show="mobileFilters" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Product filters" @keydown.escape.window="mobileFilters = false">
            <button type="button" class="absolute inset-0 bg-slate-950/50" @click="mobileFilters = false" aria-label="Close filters"></button>
            <div class="absolute inset-y-0 left-0 flex w-full max-w-sm flex-col bg-white shadow-2xl dark:bg-slate-950">
                <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="text-lg font-semibold">Filters</h2><button type="button" class="sf-icon-button" @click="mobileFilters = false" aria-label="Close filters">&times;</button></div>
                <div class="flex-1 overflow-y-auto p-5">
                    @include('storefront.collections._filters')
                </div>
                <div class="border-t border-slate-200 p-5 dark:border-slate-800"><button type="button" class="sf-button sf-button-primary w-full" @click="mobileFilters = false">Show {{ $products->total() }} products</button></div>
            </div>
        </div>

        <aside id="collection-filters" class="hidden lg:block" aria-label="Product filters">
            @include('storefront.collections._filters')
        </aside>

        <section aria-label="Products" aria-live="polite" wire:loading.class="opacity-50" wire:target="sort,inStock,minPrice,maxPrice,types,vendors,clearFilters">
            @if ($products->isEmpty())
                <div class="sf-empty-state">
                    <svg aria-hidden="true" class="mx-auto size-12 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7" stroke-width="1.5"/><path d="m20 20-4-4" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <h2 class="mt-4 text-xl font-semibold">No products found</h2>
                    <p class="mt-2 text-slate-500">Try adjusting your filters or browse the full collection.</p>
                    @if ($this->hasActiveFilters)<button wire:click="clearFilters" class="sf-button sf-button-secondary mt-5">Clear filters</button>@endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 md:grid-cols-3">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-12"><x-storefront.pagination :paginator="$products" /></div>
            @endif
        </section>
    </div>
</div>
