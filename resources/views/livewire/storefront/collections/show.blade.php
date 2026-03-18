<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        {{-- Header --}}
        <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => '/'], ['label' => 'Collections', 'url' => '/collections'], ['label' => $collection->title]]" />

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">{{ $collection->title }}</h1>
            @if($collection->description_html)
                <div class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400 prose prose-sm dark:prose-invert">
                    {!! $collection->description_html !!}
                </div>
            @endif
        </div>

        {{-- Toolbar --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <button @click="$dispatch('toggle-filters')"
                        class="flex items-center gap-1.5 rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:hidden">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    Filters
                </button>
                <span class="hidden text-sm text-zinc-500 dark:text-zinc-400 sm:inline">{{ $this->products->total() }} products</span>
            </div>
            <div>
                <select wire:model.live="sort"
                        class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-blue-500 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <option value="featured">Featured</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="newest">Newest</option>
                </select>
            </div>
        </div>

        {{-- Active filter pills --}}
        @if($inStock || $minPrice !== null || $maxPrice !== null || !empty($productTypes) || !empty($vendors))
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if($inStock)
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        In stock
                        <button wire:click="$set('inStock', false)" class="ml-1 text-zinc-400 hover:text-zinc-600">&times;</button>
                    </span>
                @endif
                @foreach($productTypes as $type)
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $type }}
                        <button wire:click="$set('productTypes', {{ json_encode(array_values(array_diff($productTypes, [$type]))) }})" class="ml-1 text-zinc-400 hover:text-zinc-600">&times;</button>
                    </span>
                @endforeach
                @foreach($vendors as $vendor)
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $vendor }}
                        <button wire:click="$set('vendors', {{ json_encode(array_values(array_diff($vendors, [$vendor]))) }})" class="ml-1 text-zinc-400 hover:text-zinc-600">&times;</button>
                    </span>
                @endforeach
                <button wire:click="clearFilters" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Clear all</button>
            </div>
        @endif

        {{-- Content: sidebar + product grid --}}
        <div class="mt-6 lg:flex lg:gap-8">
            {{-- Filter sidebar (desktop) --}}
            <aside class="hidden w-64 shrink-0 lg:block">
                <div class="space-y-6">
                    {{-- Availability --}}
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Availability</h3>
                        <label class="mt-2 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <input type="checkbox" wire:model.live="inStock"
                                   class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800">
                            In stock
                        </label>
                    </div>

                    {{-- Price range --}}
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Price</h3>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="number" wire:model.live.debounce.500ms="minPrice" placeholder="Min"
                                   class="w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            <span class="text-zinc-400">-</span>
                            <input type="number" wire:model.live.debounce.500ms="maxPrice" placeholder="Max"
                                   class="w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </div>

                    {{-- Product type --}}
                    @if(!empty($this->availableProductTypes))
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Product Type</h3>
                            <div class="mt-2 space-y-1">
                                @foreach($this->availableProductTypes as $type)
                                    <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <input type="checkbox" wire:model.live="productTypes" value="{{ $type }}"
                                               class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800">
                                        {{ $type }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Vendor --}}
                    @if(!empty($this->availableVendors))
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Vendor</h3>
                            <div class="mt-2 space-y-1">
                                @foreach($this->availableVendors as $vendor)
                                    <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <input type="checkbox" wire:model.live="vendors" value="{{ $vendor }}"
                                               class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800">
                                        {{ $vendor }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($inStock || $minPrice !== null || $maxPrice !== null || !empty($productTypes) || !empty($vendors))
                        <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            Clear all filters
                        </button>
                    @endif
                </div>
            </aside>

            {{-- Product grid --}}
            <div class="flex-1">
                @if($this->products->isEmpty())
                    <div class="py-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">No products found</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Try adjusting your filters or browse our full collection.</p>
                        <button wire:click="clearFilters" class="mt-4 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Clear filters
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3" wire:loading.class="opacity-50">
                        @foreach($this->products as $product)
                            <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
