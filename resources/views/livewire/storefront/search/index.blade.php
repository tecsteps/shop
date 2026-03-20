<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Search'],
        ]])

        <div class="mt-6">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Search</h1>
        </div>

        {{-- Search Input --}}
        <div class="mt-6">
            <form wire:submit="$refresh">
                <input type="text"
                       wire:model.live.debounce.500ms="query"
                       placeholder="Search products..."
                       class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
            </form>
        </div>

        @if($query)
            @if($results && $results->total() > 0)
                {{-- Toolbar --}}
                <div class="mt-6 flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $results->total() }} {{ Str::plural('result', $results->total()) }} for "{{ $query }}"
                    </p>
                    <select wire:model.live="sort" class="rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <option value="relevance">Relevance</option>
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                        <option value="newest">Newest</option>
                    </select>
                </div>

                <div class="mt-6 flex gap-8">
                    {{-- Filter Sidebar --}}
                    <aside class="hidden w-56 shrink-0 lg:block">
                        <div class="space-y-6">
                            {{-- Vendor Filter --}}
                            @if($vendors->isNotEmpty())
                                <div>
                                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Vendor</h3>
                                    <select wire:model.live="vendor" class="mt-2 w-full rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                        <option value="">All vendors</option>
                                        @foreach($vendors as $v)
                                            <option value="{{ $v }}">{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Price Range Filter --}}
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Price range</h3>
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="number"
                                           wire:model.live.debounce.500ms="priceMin"
                                           placeholder="Min"
                                           min="0"
                                           class="w-full rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                    <span class="text-zinc-400">-</span>
                                    <input type="number"
                                           wire:model.live.debounce.500ms="priceMax"
                                           placeholder="Max"
                                           min="0"
                                           class="w-full rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                </div>
                            </div>

                            {{-- Active Filters --}}
                            @if($vendor || $priceMin || $priceMax)
                                <div>
                                    @if($vendor)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-3 py-1 text-xs font-medium dark:bg-zinc-700 dark:text-white">
                                            {{ $vendor }}
                                            <button wire:click="$set('vendor', '')" class="ml-1 hover:text-zinc-900 dark:hover:text-white">&times;</button>
                                        </span>
                                    @endif
                                    <button wire:click="clearFilters" class="mt-2 block text-sm text-zinc-600 underline hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                        Clear all filters
                                    </button>
                                </div>
                            @endif
                        </div>
                    </aside>

                    {{-- Results Grid --}}
                    <div class="flex-1" wire:loading.class="opacity-50">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                            @foreach($results as $product)
                                @include('storefront.components.product-card', ['product' => $product])
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $results->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-12 py-16 text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <p class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">No results found</p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">No products match "{{ $query }}". Try a different search term.</p>
                </div>
            @endif
        @else
            <div class="mt-12 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <p class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">Search our products</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Enter a search term above to find products.</p>
            </div>
        @endif
    </div>
</div>
