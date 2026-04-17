<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        {{-- Breadcrumbs --}}
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => 'Collections', 'url' => route('storefront.collections.index')],
            ['label' => $collection->title],
        ]" class="mb-6" />

        {{-- Collection Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $collection->title }}</h1>
            @if ($collection->description_html)
                <div class="mt-3 prose dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
                    {!! $collection->description_html !!}
                </div>
            @endif
        </div>

        {{-- Toolbar --}}
        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            </p>
            <div class="flex items-center gap-4">
                @if (count($vendors) > 0)
                    <select
                        wire:model.live="vendor"
                        class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Vendors</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                        @endforeach
                    </select>
                @endif

                <select
                    wire:model.live="sort"
                    class="text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="newest">Newest</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="title-asc">Title: A-Z</option>
                </select>
            </div>
        </div>

        {{-- Price Range Filters --}}
        <div class="flex items-center gap-4 mb-6">
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
        </div>

        {{-- Product Grid --}}
        <div wire:loading.class="opacity-50 transition-opacity">
            @if ($products->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <flux:icon name="magnifying-glass" class="size-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No products found</h3>
                    <p class="text-zinc-500 dark:text-zinc-400 mb-4">Try adjusting your filters or browse our full collection.</p>
                    <flux:button wire:click="$set('vendor', ''); $set('priceMin', null); $set('priceMax', null)" variant="primary">
                        Clear filters
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
