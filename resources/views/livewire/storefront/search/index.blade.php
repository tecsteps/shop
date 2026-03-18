<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => '/'], ['label' => 'Search']]" />

        <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">Search</h1>

        {{-- Search input --}}
        <div class="mt-6">
            <label for="search-input" class="sr-only">Search products</label>
            <div class="relative max-w-lg">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input type="search" id="search-input" wire:model.live.debounce.300ms="query"
                       placeholder="Search products..."
                       class="w-full rounded-lg border border-zinc-300 py-3 pl-10 pr-4 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500">
            </div>
        </div>

        @if(trim($query) !== '')
            {{-- Toolbar --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->results->total() }} {{ $this->results->total() === 1 ? 'result' : 'results' }}</span>
                </div>
                <div>
                    <select wire:model.live="sort"
                            class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-blue-500 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <option value="relevance">Relevance</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="newest">Newest</option>
                    </select>
                </div>
            </div>

            {{-- Active filter pills --}}
            @if($vendor || $minPrice !== null || $maxPrice !== null || $collectionId)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if($vendor)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $vendor }}
                            <button wire:click="$set('vendor', null)" class="ml-1 text-zinc-400 hover:text-zinc-600">&times;</button>
                        </span>
                    @endif
                    @if($minPrice !== null || $maxPrice !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            @if($minPrice !== null && $maxPrice !== null)
                                {{ number_format($minPrice / 100, 2) }} - {{ number_format($maxPrice / 100, 2) }}
                            @elseif($minPrice !== null)
                                From {{ number_format($minPrice / 100, 2) }}
                            @else
                                Up to {{ number_format($maxPrice / 100, 2) }}
                            @endif
                            <button wire:click="$set('minPrice', null); $set('maxPrice', null)" class="ml-1 text-zinc-400 hover:text-zinc-600">&times;</button>
                        </span>
                    @endif
                    <button wire:click="clearFilters" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Clear all</button>
                </div>
            @endif

            {{-- Content: sidebar + product grid --}}
            <div class="mt-6 lg:flex lg:gap-8">
                {{-- Filter sidebar --}}
                <aside class="hidden w-64 shrink-0 lg:block">
                    <div class="space-y-6">
                        {{-- Vendor filter --}}
                        @if(!empty($this->availableVendors))
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Vendor</h3>
                                <div class="mt-2 space-y-1">
                                    @foreach($this->availableVendors as $v)
                                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            <input type="radio" wire:model.live="vendor" value="{{ $v }}"
                                                   class="border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800"
                                                   {{ $vendor === $v ? 'checked' : '' }}>
                                            {{ $v }}
                                        </label>
                                    @endforeach
                                    @if($vendor)
                                        <button wire:click="$set('vendor', null)" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            Clear vendor
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Price range filter --}}
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

                        {{-- Collection filter --}}
                        @if($this->availableCollections->isNotEmpty())
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Collection</h3>
                                <div class="mt-2 space-y-1">
                                    @foreach($this->availableCollections as $c)
                                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            <input type="radio" wire:model.live="collectionId" value="{{ $c->id }}"
                                                   class="border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800"
                                                   {{ $collectionId == $c->id ? 'checked' : '' }}>
                                            {{ $c->title }}
                                        </label>
                                    @endforeach
                                    @if($collectionId)
                                        <button wire:click="$set('collectionId', null)" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            Clear collection
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($vendor || $minPrice !== null || $maxPrice !== null || $collectionId)
                            <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                Clear all filters
                            </button>
                        @endif
                    </div>
                </aside>

                {{-- Product grid --}}
                <div class="flex-1">
                    @if($this->results->isEmpty())
                        <div class="py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">No results found</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Try a different search term or adjust your filters.</p>
                            @if($vendor || $minPrice !== null || $maxPrice !== null || $collectionId)
                                <button wire:click="clearFilters" class="mt-4 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                    Clear filters
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3" wire:loading.class="opacity-50">
                            @foreach($this->results as $product)
                                <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $this->results->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Empty state --}}
            <div class="mt-12 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">Search our store</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Type a keyword above to find products.</p>
            </div>
        @endif
    </div>
</div>
