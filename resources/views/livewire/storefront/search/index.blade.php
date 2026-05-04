<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Search results']]" />

    <div class="mt-6 max-w-3xl space-y-4">
        <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">
            @if (trim($q) !== '')
                {{ $products->total() }} results for "{{ $q }}"
            @else
                Search products
            @endif
        </h1>

        <flux:input wire:model.live.debounce.300ms="q" icon="magnifying-glass" placeholder="Search products..." aria-label="Search products" />
    </div>

    <div class="mt-10 grid gap-8 lg:grid-cols-[240px_1fr]">
        <aside class="space-y-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold">Filters</h2>
                <button wire:click="clearFilters" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300">Clear all</button>
            </div>

            <div class="space-y-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                <flux:checkbox wire:model.live="inStock" label="In stock" />

                <div class="space-y-3">
                    <h3 class="text-sm font-medium">Price</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model.live.debounce.500ms="minPrice" type="number" min="0" placeholder="Min" aria-label="Minimum price" />
                        <flux:input wire:model.live.debounce.500ms="maxPrice" type="number" min="0" placeholder="Max" aria-label="Maximum price" />
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-sm font-medium">Product type</h3>
                    <div class="space-y-2">
                        @foreach ($productTypes as $type)
                            <flux:checkbox wire:model.live="types" value="{{ $type }}" :label="$type" wire:key="search-type-filter-{{ $type }}" />
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-sm font-medium">Vendor</h3>
                    <div class="space-y-2">
                        @foreach ($productVendors as $vendor)
                            <flux:checkbox wire:model.live="vendors" value="{{ $vendor }}" :label="$vendor" wire:key="search-vendor-filter-{{ $vendor }}" />
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>

        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $products->total() }} products</p>

                <flux:select wire:model.live="sort" aria-label="Sort products" class="sm:max-w-56">
                    <flux:select.option value="relevance">Relevance</flux:select.option>
                    <flux:select.option value="price_asc">Price: Low to High</flux:select.option>
                    <flux:select.option value="price_desc">Price: High to Low</flux:select.option>
                    <flux:select.option value="newest">Newest</flux:select.option>
                </flux:select>
            </div>

            @if ($products->isEmpty())
                <div class="rounded-lg border border-zinc-200 px-6 py-16 text-center dark:border-zinc-800">
                    <div class="mx-auto flex max-w-md flex-col items-center gap-3">
                        <flux:icon name="magnifying-glass" class="size-10 text-zinc-400" />
                        <h2 class="text-lg font-semibold">No products found</h2>
                        <p class="text-zinc-600 dark:text-zinc-400">Try another search term or adjust your filters.</p>
                        <flux:button wire:click="clearFilters" variant="ghost">Clear filters</flux:button>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3" wire:loading.class="opacity-50">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" wire:key="search-product-{{ $product->getKey() }}" />
                    @endforeach
                </div>

                {{ $products->links() }}
            @endif
        </div>
    </div>
</section>
