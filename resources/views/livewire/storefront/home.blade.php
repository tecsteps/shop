<div>
<section class="relative overflow-hidden bg-zinc-900 text-white">
    <div class="mx-auto flex min-h-[520px] max-w-7xl items-center px-4 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="mb-5 text-sm font-semibold uppercase tracking-[0.25em] text-blue-300">{{ data_get($store->settings?->general_json, 'store_name', $store->name) }}</p>
            <h1 class="text-5xl font-bold tracking-tight sm:text-7xl">{{ data_get($store->settings?->settings_json, 'hero_title', 'Everyday pieces, thoughtfully made.') }}</h1>
            <p class="mt-6 max-w-xl text-lg text-zinc-300">{{ data_get($store->settings?->settings_json, 'hero_subtitle', 'Timeless wardrobe essentials with an easy, modern fit.') }}</p>
            <a href="{{ route('collection.show', 'new-arrivals') }}" class="mt-8 inline-flex rounded-full bg-white px-6 py-3 font-semibold text-zinc-900 hover:bg-blue-100" wire:navigate>Shop new arrivals</a>
        </div>
    </div>
</section>
<section class="mx-auto max-w-7xl px-4 py-16 lg:px-8">
    <div class="flex items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Explore</p><h2 class="mt-2 text-3xl font-bold">Featured collections</h2></div><a class="text-sm font-semibold underline" href="{{ route('collections.index') }}" wire:navigate>View all</a></div>
    <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">@foreach ($collections as $collection)<a wire:key="collection-{{ $collection->id }}" href="{{ route('collection.show', $collection->handle) }}" class="rounded-2xl bg-zinc-100 p-6 transition hover:-translate-y-1 dark:bg-zinc-800" wire:navigate><p class="text-lg font-semibold">{{ $collection->title }}</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $collection->products_count }} products</p></a>@endforeach</div>
</section>
<section class="bg-zinc-50 dark:bg-zinc-900"><div class="mx-auto max-w-7xl px-4 py-16 lg:px-8"><div class="flex items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Curated for you</p><h2 class="mt-2 text-3xl font-bold">Featured products</h2></div></div><div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 lg:grid-cols-4">@foreach ($products as $product)<x-storefront.product-card :product="$product" />@endforeach</div></div></section>
<section class="mx-auto max-w-3xl px-4 py-16 text-center"><h2 class="text-3xl font-bold">Stay in the loop</h2><p class="mt-3 text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and updates.</p><form class="mx-auto mt-6 flex max-w-lg gap-3" action="#" method="post"><label class="sr-only" for="newsletter-email">Email</label><input id="newsletter-email" type="email" placeholder="Enter your email" class="min-w-0 flex-1 rounded-full border border-zinc-300 bg-white px-5 py-3 dark:border-zinc-700 dark:bg-zinc-900"><button type="submit" class="rounded-full bg-blue-600 px-5 py-3 font-semibold text-white">Subscribe</button></form></section>
</div>
