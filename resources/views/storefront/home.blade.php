<x-storefront.layout :title="'Home'">
    <section class="bg-zinc-100 dark:bg-zinc-900">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-16 lg:grid-cols-[1.1fr_.9fr] lg:items-center">
            <div class="grid gap-6">
                <h1 class="max-w-3xl text-4xl font-bold tracking-normal text-zinc-950 dark:text-white md:text-6xl">{{ app('current_store')->settings?->settings_json['hero_heading'] ?? app('current_store')->name }}</h1>
                <p class="max-w-2xl text-lg text-zinc-700 dark:text-zinc-300">{{ app('current_store')->settings?->settings_json['hero_subheading'] ?? 'Products ready for checkout.' }}</p>
                <div>
                    <a href="{{ route('collections.show', 'new-arrivals') }}" class="inline-flex rounded-md bg-zinc-950 px-5 py-3 font-medium text-white dark:bg-white dark:text-zinc-950">Shop new arrivals</a>
                </div>
            </div>
            <img src="https://placehold.co/1000x800/d4d4d8/18181b?text=Acme+Fashion" alt="Acme Fashion featured products" class="aspect-[5/4] w-full rounded-lg object-cover">
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-normal">Featured collections</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Browse the seeded catalog by collection.</p>
            </div>
            <a class="text-sm font-medium" href="{{ route('collections.index') }}">View all</a>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($collections as $collection)
                <a href="{{ route('collections.show', $collection->handle) }}" class="rounded-lg border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                    <h3 class="font-semibold">{{ $collection->title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ strip_tags($collection->description_html) }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="mb-6">
            <h2 class="text-2xl font-bold tracking-normal">Featured products</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Active products only. Drafts are hidden from the storefront.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($products as $product)
                <x-shop.product-card :product="$product" />
            @endforeach
        </div>
    </section>
</x-storefront.layout>

