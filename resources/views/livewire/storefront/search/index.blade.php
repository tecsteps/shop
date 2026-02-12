<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Search', 'url' => ''],
    ]" />

    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-6">
        @if($products && strlen($q) >= 2)
            {{ $products->total() }} {{ Str::plural('result', $products->total()) }} for "{{ $q }}"
        @else
            Search
        @endif
    </h1>

    {{-- Search input --}}
    <div class="mb-8">
        <label for="search-input" class="sr-only">Search products</label>
        <div class="relative max-w-xl">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" id="search-input"
                   wire:model.live.debounce.300ms="q"
                   placeholder="Search products..."
                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    @if($products)
        {{-- Sort --}}
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            </p>
            <div>
                <label for="search-sort" class="sr-only">Sort by</label>
                <select wire:model.live="sort" id="search-sort"
                        class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="relevance">Relevance</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="title-asc">Title: A-Z</option>
                    <option value="newest">Newest</option>
                </select>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="text-center py-16">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No results found</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No products found for "{{ $q }}". Try a different search term.</p>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6" wire:loading.class="opacity-50">
                @foreach($products as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    @elseif(strlen($q) > 0 && strlen($q) < 2)
        <p class="text-sm text-gray-500 dark:text-gray-400">Please enter at least 2 characters to search.</p>
    @endif
</div>
