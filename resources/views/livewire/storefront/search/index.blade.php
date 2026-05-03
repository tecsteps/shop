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

    <div class="mt-8">
        @if ($products->isEmpty())
            <div class="rounded-lg border border-zinc-200 px-6 py-16 text-center dark:border-zinc-800">
                <div class="mx-auto flex max-w-md flex-col items-center gap-3">
                    <flux:icon name="magnifying-glass" class="size-10 text-zinc-400" />
                    <h2 class="text-lg font-semibold">No products found</h2>
                    <p class="text-zinc-600 dark:text-zinc-400">Try another search term or browse a collection.</p>
                    <flux:button :href="route('collections.index')" wire:navigate variant="ghost">Browse collections</flux:button>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4" wire:loading.class="opacity-50">
                @foreach ($products as $product)
                    <x-storefront.product-card :product="$product" wire:key="search-product-{{ $product->getKey() }}" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</section>
