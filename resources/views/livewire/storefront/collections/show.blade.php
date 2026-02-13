<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ $collection->title }}</h1>
            @if($collection->description_html)
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ strip_tags($collection->description_html) }}
                </p>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="mt-6 flex flex-wrap items-end gap-4">
        <flux:field>
            <flux:select wire:model.live="sort" label="Sort by">
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:input wire:model.live.debounce.500ms="minPrice" type="number" label="Min Price" placeholder="0" />
        </flux:field>

        <flux:field>
            <flux:input wire:model.live.debounce.500ms="maxPrice" type="number" label="Max Price" placeholder="Any" />
        </flux:field>
    </div>

    {{-- Products Grid --}}
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <a wire:key="product-{{ $product->id }}"
               href="{{ route('storefront.products.show', $product->handle) }}"
               class="group rounded-lg border border-zinc-200 p-4 transition-colors hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                <div class="aspect-square rounded-md bg-zinc-100 dark:bg-zinc-800"></div>
                <div class="mt-4">
                    <h3 class="text-sm font-semibold group-hover:text-zinc-600 dark:group-hover:text-zinc-300">
                        {{ $product->title }}
                    </h3>
                    @if($product->variants->first())
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            ${{ number_format($product->variants->first()->price_amount / 100, 2) }}
                        </p>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-zinc-500 dark:text-zinc-400">No products in this collection.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>
