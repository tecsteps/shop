<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-8">Search</h1>

        {{-- Search input with autocomplete --}}
        <div class="max-w-xl relative" x-data="{ open: @entangle('showSuggestions') }" @click.away="open = false">
            <form wire:submit="submitSearch" class="flex gap-2">
                <div class="relative flex-1">
                    <flux:input
                        wire:model.live.debounce.300ms="autocompleteQuery"
                        type="search"
                        placeholder="Search products..."
                        autofocus
                        @focus="if ($wire.suggestions.length) open = true"
                    />

                    {{-- Autocomplete dropdown --}}
                    <div
                        x-show="open"
                        x-transition
                        x-cloak
                        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 z-50 overflow-hidden"
                    >
                        @foreach ($suggestions as $suggestion)
                            <button
                                type="button"
                                wire:click="selectSuggestion('{{ $suggestion['handle'] }}')"
                                wire:key="suggestion-{{ $suggestion['id'] }}"
                                class="w-full text-left px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 flex items-center gap-3"
                            >
                                <flux:icon name="magnifying-glass" class="size-4 text-zinc-400 shrink-0" />
                                {{ $suggestion['title'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <flux:button type="submit" variant="primary">Search</flux:button>
            </form>
        </div>

        @if ($query !== '')
            {{-- Filters toolbar --}}
            <div class="mt-6 flex flex-wrap items-center gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->products->total() }} {{ Str::plural('result', $this->products->total()) }} for "{{ $query }}"
                </p>

                <div class="flex items-center gap-4 ml-auto flex-wrap">
                    @if (count($this->vendors) > 0)
                        <select
                            wire:model.live="vendor"
                            class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Vendors</option>
                            @foreach ($this->vendors as $v)
                                <option value="{{ $v }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($this->collections->count() > 0)
                        <select
                            wire:model.live="collection"
                            class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Collections</option>
                            @foreach ($this->collections as $col)
                                <option value="{{ $col->id }}">{{ $col->title }}</option>
                            @endforeach
                        </select>
                    @endif

                    <select
                        wire:model.live="sort"
                        class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="relevance">Relevance</option>
                        <option value="newest">Newest</option>
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                    </select>
                </div>
            </div>

            {{-- Price range --}}
            <div class="flex items-center gap-4 mt-4 mb-6">
                <div class="flex items-center gap-2">
                    <label for="priceMin" class="text-sm text-zinc-500 dark:text-zinc-400">Min</label>
                    <input
                        type="number"
                        id="priceMin"
                        wire:model.live.debounce.500ms="priceMin"
                        placeholder="0"
                        class="w-24 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label for="priceMax" class="text-sm text-zinc-500 dark:text-zinc-400">Max</label>
                    <input
                        type="number"
                        id="priceMax"
                        wire:model.live.debounce.500ms="priceMax"
                        placeholder="999"
                        class="w-24 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                @if ($vendor || $priceMin || $priceMax || $collection || $sort !== 'relevance')
                    <flux:button wire:click="clearFilters" size="sm" variant="ghost">
                        Clear filters
                    </flux:button>
                @endif
            </div>

            {{-- Results grid --}}
            <div wire:loading.class="opacity-50 transition-opacity">
                @if ($this->products->count())
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4 sm:gap-6">
                        @foreach ($this->products as $product)
                            <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <flux:icon name="magnifying-glass" class="size-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No results found</h3>
                        <p class="text-zinc-500 dark:text-zinc-400 mb-4">Try a different search term or adjust your filters.</p>
                        <flux:button wire:click="clearFilters" variant="primary">
                            Clear filters
                        </flux:button>
                    </div>
                @endif
            </div>
        @else
            {{-- Empty state --}}
            <div class="mt-8 text-center py-16">
                <flux:icon name="magnifying-glass" class="size-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Search our store</h3>
                <p class="text-zinc-500 dark:text-zinc-400">
                    Type a search term above to find products.
                </p>
            </div>
        @endif
    </div>
</div>
