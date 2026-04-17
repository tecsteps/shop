<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Header --}}
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections', 'url' => route('storefront.collections.index')],
        ['label' => $collection->title],
    ]" />

    <div class="mt-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $collection->title }}</h1>
        @if($collection->description_html)
            <div class="mt-3 prose dark:prose-invert max-w-3xl text-zinc-600 dark:text-zinc-400">
                {!! $collection->description_html !!}
            </div>
        @endif
    </div>

    {{-- Toolbar --}}
    <div class="mt-8 flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-4">
        <div class="flex items-center gap-4">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            </span>
        </div>
        <div>
            <label for="sort" class="sr-only">Sort by</label>
            <select id="sort"
                    wire:model.live="sort"
                    class="rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="featured">Featured</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="newest">Newest</option>
            </select>
        </div>
    </div>

    <div class="mt-6 lg:grid lg:grid-cols-4 lg:gap-8">
        {{-- Filter Sidebar --}}
        <aside class="hidden lg:block">
            @if($this->hasActiveFilters())
                <button wire:click="clearFilters" class="mb-4 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Clear all filters
                </button>
            @endif

            {{-- In Stock --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 py-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Availability</h3>
                <label class="mt-3 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer">
                    <input type="checkbox" wire:model.live="inStock" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                    In stock
                </label>
            </div>

            {{-- Price Range --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 py-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Price</h3>
                <div class="mt-3 flex items-center gap-2">
                    <input type="number"
                           wire:model.live.debounce.500ms="minPrice"
                           placeholder="Min"
                           class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-zinc-400">-</span>
                    <input type="number"
                           wire:model.live.debounce.500ms="maxPrice"
                           placeholder="Max"
                           class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Product Type --}}
            @if(count($productTypes) > 0)
                <div class="border-b border-zinc-200 dark:border-zinc-700 py-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Product Type</h3>
                    <div class="mt-3 space-y-2">
                        @foreach($productTypes as $type)
                            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                <input type="checkbox" wire:model.live="selectedTypes" value="{{ $type }}" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                                {{ $type }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Vendor --}}
            @if(count($vendors) > 0)
                <div class="py-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Vendor</h3>
                    <div class="mt-3 space-y-2">
                        @foreach($vendors as $vendor)
                            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                <input type="checkbox" wire:model.live="selectedVendors" value="{{ $vendor }}" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                                {{ $vendor }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>

        {{-- Product Grid --}}
        <div class="lg:col-span-3" wire:loading.class="opacity-50">
            @if($products->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:gap-6">
                    @foreach($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-8">
                    <x-storefront.pagination :paginator="$products" />
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">No products found</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Try adjusting your filters or browse our full collection.
                    </p>
                    @if($this->hasActiveFilters())
                        <button wire:click="clearFilters"
                                class="mt-4 rounded-md border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            Clear filters
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
