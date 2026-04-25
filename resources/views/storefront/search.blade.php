<x-storefront.layout :title="'Search'">
    <section class="mx-auto max-w-7xl px-4 py-10">
        <h1 class="text-3xl font-bold tracking-normal">Search</h1>
        <form method="GET" class="mt-6 flex gap-3">
            <label class="sr-only" for="q">Search products</label>
            <input id="q" name="q" value="{{ $query }}" placeholder="Search products" class="min-w-0 flex-1 rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <button class="rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950">Search</button>
        </form>
        @if($query !== '')
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $products->count() }} results for "{{ $query }}"</p>
        @endif
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($products as $product)
                <x-shop.product-card :product="$product" />
            @empty
                @if($query !== '')
                    <p>No results found.</p>
                @endif
            @endforelse
        </div>
    </section>
</x-storefront.layout>

