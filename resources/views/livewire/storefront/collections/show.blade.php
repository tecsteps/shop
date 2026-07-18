<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Collections', 'url' => route('storefront.collections.index')],
        ['label' => $collection->title, 'url' => null],
    ]" />

    <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $collection->title }}</h1>
            @if ($collection->description_html)
                <div class="prose prose-zinc dark:prose-invert mt-2 max-w-2xl text-sm">{!! $collection->description_html !!}</div>
            @endif
        </div>

        <label class="flex items-center gap-2 text-sm">
            <span class="text-zinc-500 dark:text-zinc-400">Sort by</span>
            <select wire:model.live="sort" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="featured">Featured</option>
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </select>
        </label>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[240px_1fr]">
        <aside aria-label="Filters">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Filters</h2>
                @if ($this->hasActiveFilters())
                    <button type="button" wire:click="clearFilters" class="text-xs text-blue-600 hover:underline dark:text-blue-400">Clear all</button>
                @endif
            </div>

            <div class="mt-4 space-y-6">
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model.live="inStock" class="rounded border-zinc-300 text-blue-600 dark:border-zinc-700" />
                    In stock only
                </label>

                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Price</p>
                    <div class="mt-2 flex items-center gap-2">
                        <flux:input type="number" wire:model.live.debounce.500ms="priceMin" placeholder="Min" size="sm" aria-label="Minimum price" />
                        <span class="text-zinc-400">-</span>
                        <flux:input type="number" wire:model.live.debounce.500ms="priceMax" placeholder="Max" size="sm" aria-label="Maximum price" />
                    </div>
                </div>

                @if ($this->availableProductTypes->isNotEmpty())
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">Product type</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($this->availableProductTypes as $type)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <input type="checkbox" wire:model.live="productTypes" value="{{ $type }}" class="rounded border-zinc-300 text-blue-600 dark:border-zinc-700" />
                                    {{ $type }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->availableVendors->isNotEmpty())
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">Vendor</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($this->availableVendors as $vendor)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <input type="checkbox" wire:model.live="vendors" value="{{ $vendor }}" class="rounded border-zinc-300 text-blue-600 dark:border-zinc-700" />
                                    {{ $vendor }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </aside>

        <div>
            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <flux:icon name="magnifying-glass" class="size-12 text-zinc-300 dark:text-zinc-700" />
                    <p class="mt-4 text-zinc-500 dark:text-zinc-400">No products match your filters</p>
                    @if ($this->hasActiveFilters())
                        <flux:button wire:click="clearFilters" variant="filled" class="mt-4">Clear filters</flux:button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
