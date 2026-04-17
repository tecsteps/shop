<div class="space-y-10">
    <header class="space-y-3">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Search</h1>
        <div class="max-w-xl">
            <input
                type="search"
                wire:model.live.debounce.300ms="q"
                placeholder="Search products..."
                class="w-full rounded-full border border-zinc-300 bg-white px-5 py-3 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            />
        </div>
    </header>

    @if (trim($q) === '')
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Start typing to search the catalogue.</p>
    @elseif ($products->isEmpty())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">No products found for "{{ $q }}".</p>
    @else
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>

        <div>
            {{ $products->links() }}
        </div>
    @endif
</div>
