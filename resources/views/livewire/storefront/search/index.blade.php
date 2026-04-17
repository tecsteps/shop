@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
    $hasFilters = $vendor !== null || $minPrice !== null || $maxPrice !== null;
@endphp

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Search results'],
        ]" />

        {{-- Search Header --}}
        <div class="mb-8">
            @if($query !== '')
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $totalResults }} {{ $totalResults === 1 ? 'result' : 'results' }} for "{{ $query }}"
                </h1>
            @else
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Search</h1>
            @endif
        </div>

        {{-- Search Input --}}
        <div class="mb-8">
            <div class="relative max-w-xl">
                <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text"
                       wire:model.live.debounce.300ms="query"
                       placeholder="Search products..."
                       class="w-full rounded-lg border border-gray-300 bg-white py-3 pl-10 pr-4 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500">
            </div>
        </div>

        @if($query !== '')
            {{-- Toolbar --}}
            <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $totalResults }} {{ $totalResults === 1 ? 'product' : 'products' }}
                </p>
                <div class="flex items-center gap-4">
                    <label for="sort" class="sr-only">Sort</label>
                    <select wire:model.live="sort"
                            id="sort"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <option value="relevance">Relevance</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="newest">Newest</option>
                    </select>
                </div>
            </div>

            {{-- Active Filter Pills --}}
            @if($hasFilters)
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @if($vendor !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $vendor }}
                            <button wire:click="$set('vendor', null)" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Remove vendor filter">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </span>
                    @endif
                    @if($minPrice !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Min: {{ $minPrice }} {{ $currency }}
                            <button wire:click="$set('minPrice', null)" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Remove min price filter">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </span>
                    @endif
                    @if($maxPrice !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Max: {{ $maxPrice }} {{ $currency }}
                            <button wire:click="$set('maxPrice', null)" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Remove max price filter">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </span>
                    @endif
                    <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        Clear all
                    </button>
                </div>
            @endif

            <div class="lg:flex lg:gap-8">
                {{-- Filter Sidebar --}}
                <aside class="hidden w-64 shrink-0 lg:block">
                    @if($hasFilters)
                        <button wire:click="clearFilters" class="mb-4 text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            Clear all filters
                        </button>
                    @endif

                    <div class="space-y-6">
                        {{-- Vendor --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Vendor</h3>
                            <input type="text"
                                   wire:model.live.debounce.500ms="vendor"
                                   placeholder="Filter by vendor"
                                   class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        </div>

                        {{-- Price Range --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Price</h3>
                            <div class="mt-2 flex gap-2">
                                <input type="number"
                                       wire:model.live.debounce.500ms="minPrice"
                                       placeholder="Min"
                                       class="w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <input type="number"
                                       wire:model.live.debounce.500ms="maxPrice"
                                       placeholder="Max"
                                       class="w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Product Grid --}}
                <div class="flex-1" wire:loading.class="opacity-50">
                    @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->isEmpty())
                        <div class="py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No results found for "{{ $query }}"</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Try a different search term or adjust your filters.</p>
                            @if($hasFilters)
                                <button wire:click="clearFilters" class="mt-4 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Clear filters
                                </button>
                            @endif
                        </div>
                    @elseif($products === null)
                        <div class="py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Search our store</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Enter a search term above to find products.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:gap-6">
                            @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                @foreach($products as $product)
                                    <div wire:key="product-{{ $product->id }}">
                                        <x-storefront.product-card :product="$product" :currency="$currency" />
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
                            <div class="mt-8">
                                {{ $products->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
