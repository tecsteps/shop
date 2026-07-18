<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Search results', 'url' => null],
    ]" />

    <div class="mt-4">
        <flux:input
            wire:model.live.debounce.400ms="query"
            type="search"
            icon="magnifying-glass"
            placeholder="Search products..."
            class="max-w-lg"
            aria-label="Search products"
            autofocus
        />
    </div>

    @if (trim($query) === '')
        <p class="mt-10 text-zinc-500 dark:text-zinc-400">Start typing to search our products.</p>
    @else
        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white">
            {{ $products->total() }} results for "{{ $query }}"
        </h1>

        @if ($collections->isNotEmpty())
            <div class="mt-6">
                <h2 class="text-sm font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Collections</h2>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($collections as $collection)
                        <a href="{{ route('storefront.collections.show', $collection->handle) }}" wire:navigate wire:key="search-collection-{{ $collection->id }}" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400 dark:border-zinc-800 dark:text-zinc-200">
                            {{ $collection->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8">
            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <flux:icon name="magnifying-glass" class="size-12 text-zinc-300 dark:text-zinc-700" />
                    <p class="mt-4 text-zinc-500 dark:text-zinc-400">No results for "{{ $query }}"</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" wire:key="search-product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
