<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-normal">Search</h1>
    <label class="mt-6 block max-w-xl">
        <span class="sr-only">Search products</span>
        <input wire:model.live="q" type="search" placeholder="Search products" class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-base text-zinc-950 outline-none focus:border-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-white">
    </label>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($products as $product)
            @include('storefront.components.product-card', ['product' => $product])
        @empty
            <div class="rounded-lg border border-zinc-200 p-6 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                No products found.
            </div>
        @endforelse
    </div>
</div>
