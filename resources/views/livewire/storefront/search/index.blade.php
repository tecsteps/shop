<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.components.breadcrumbs :items="[
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Search results', 'url' => null],
        ]" class="mb-6" />

        <h1 class="mb-8 text-3xl font-bold text-gray-900 dark:text-white">
            @if($q)
                Search results for "{{ $q }}"
            @else
                Search
            @endif
        </h1>

        {{-- Search input --}}
        <div class="mb-8">
            <form wire:submit="$refresh" class="flex gap-2">
                <input type="text"
                       wire:model.live.debounce.300ms="q"
                       placeholder="Search products..."
                       class="flex-1 rounded-md border border-gray-300 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                       autofocus />
            </form>
        </div>

        @if($q)
            <div class="lg:grid lg:grid-cols-4 lg:gap-8">
                {{-- Filters sidebar --}}
                <aside class="mb-6 lg:col-span-1 lg:mb-0">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Filters</h2>

                        {{-- Vendor filter --}}
                        @if($vendors->isNotEmpty())
                            <div class="mb-4">
                                <label for="vendor-filter" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Vendor</label>
                                <select id="vendor-filter"
                                        wire:model.live="vendor"
                                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <option value="">All vendors</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Price range --}}
                        <div class="mb-4">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Price range (cents)</label>
                            <div class="flex gap-2">
                                <input type="number"
                                       wire:model.live.debounce.500ms="priceMin"
                                       placeholder="Min"
                                       class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300" />
                                <input type="number"
                                       wire:model.live.debounce.500ms="priceMax"
                                       placeholder="Max"
                                       class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300" />
                            </div>
                        </div>

                        {{-- Collection filter --}}
                        @if($collections->isNotEmpty())
                            <div class="mb-4">
                                <label for="collection-filter" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Collection</label>
                                <select id="collection-filter"
                                        wire:model.live="collectionId"
                                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <option value="">All collections</option>
                                    @foreach($collections as $collection)
                                        <option value="{{ $collection->id }}">{{ $collection->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($vendor || $priceMin || $priceMax || $collectionId)
                            <button wire:click="clearFilters"
                                    class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                Clear all filters
                            </button>
                        @endif
                    </div>
                </aside>

                {{-- Results --}}
                <div class="lg:col-span-3">
                    {{-- Toolbar --}}
                    <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if($products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                                {{ $products->total() }} {{ Str::plural('result', $products->total()) }}
                            @endif
                        </p>
                        <div class="flex items-center gap-2">
                            <label for="sort" class="sr-only">Sort by</label>
                            <select id="sort"
                                    wire:model.live="sort"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <option value="relevance">Relevance</option>
                                <option value="price-asc">Price: Low to High</option>
                                <option value="price-desc">Price: High to Low</option>
                                <option value="newest">Newest</option>
                            </select>
                        </div>
                    </div>

                    @if($products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $products->isNotEmpty())
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6"
                             wire:loading.class="opacity-50">
                            @foreach($products as $product)
                                <x-storefront.components.product-card :product="$product" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @else
                        <div class="py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No results found</h2>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Try a different search term or adjust your filters.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <p class="mt-4 text-gray-500 dark:text-gray-400">
                    Enter a search term to find products.
                </p>
                <a href="/"
                   class="mt-6 inline-block rounded-md border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    Continue shopping
                </a>
            </div>
        @endif
    </div>
</div>
