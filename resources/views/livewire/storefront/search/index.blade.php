<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Search results']]" />

    @if (trim($query) === '')
        <h1 class="mt-4 text-3xl font-bold">Search</h1>
    @elseif ($paginator)
        <h1 class="mt-4 text-3xl font-bold">{{ $paginator->total() }} results for '{{ $query }}'</h1>
    @endif

    <form wire:submit="search" class="mt-4 flex max-w-xl gap-2">
        <flux:input wire:model="query" type="search" placeholder="Search products..." aria-label="Search products" class="flex-1" />
        <flux:button type="submit" variant="primary">Search</flux:button>
    </form>

    @if (trim($query) !== '' && $paginator)
        <div class="mt-6 flex items-center justify-between gap-4 border-y border-zinc-200 py-3 dark:border-zinc-800">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="inStock" class="h-4 w-4" />
                In stock only
            </label>
            <div class="flex items-center gap-2">
                <label for="search-sort" class="text-sm">Sort</label>
                <select id="search-sort" wire:model.live="sort" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="relevance">Relevance</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="newest">Newest</option>
                    <option value="best_selling">Best Selling</option>
                </select>
            </div>
        </div>

        @if ($paginator->isEmpty())
            <p class="mt-8 text-center text-zinc-500 dark:text-zinc-400">No results found for '{{ $query }}'. Try a different search term.</p>
        @else
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($paginator as $row)
                    <a href="{{ route('storefront.products.show', ['handle' => $row['handle']]) }}" wire:key="search-{{ $row['id'] }}" class="group flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="aspect-square bg-zinc-100 dark:bg-zinc-800">
                            @if ($row['image_url'])
                                <img src="{{ $row['image_url'] }}" alt="{{ $row['title'] }}" loading="lazy" class="h-full w-full object-cover" />
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-1 p-4">
                            <p class="line-clamp-2 text-sm font-semibold">{{ $row['title'] }}</p>
                            <x-storefront.price :amount="$row['price_amount']" :currency="$row['currency']" :compareAtAmount="$row['compare_at_amount']" class="text-sm" />
                        </div>
                    </a>
                @endforeach
            </div>
            @if ($paginator->hasPages())
                <nav aria-label="Pagination" class="mt-8 flex items-center justify-center gap-2">
                    <flux:button wire:click="previousPage" variant="ghost" size="sm" :disabled="$paginator->onFirstPage()">Previous</flux:button>
                    <span class="text-sm">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
                    <flux:button wire:click="nextPage" variant="ghost" size="sm" :disabled="! $paginator->hasMorePages()">Next</flux:button>
                </nav>
            @endif
        @endif
    @endif
</div>
