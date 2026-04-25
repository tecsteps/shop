<x-storefront.layout :title="$collection->title">
    <section class="mx-auto max-w-7xl px-4 py-10">
        <nav class="text-sm text-zinc-600 dark:text-zinc-400"><a href="{{ route('collections.index') }}">Collections</a> / {{ $collection->title }}</nav>
        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-normal">{{ $collection->title }}</h1>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ strip_tags($collection->description_html) }}</p>
            </div>
            <form method="GET">
                <label class="sr-only" for="sort">Sort</label>
                <select id="sort" name="sort" class="rounded-md border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <option>Featured</option>
                    <option>Price low to high</option>
                </select>
            </form>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($products as $product)
                <x-shop.product-card :product="$product" />
            @empty
                <p>No products found.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    </section>
</x-storefront.layout>

