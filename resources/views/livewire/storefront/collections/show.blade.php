<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <a href="/collections" class="text-sm font-semibold text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">Collections</a>
        <h1 class="mt-3 text-3xl font-semibold tracking-normal">{{ $collection->title }}</h1>
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ strip_tags($collection->description_html) }}</p>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($collection->products as $product)
            @include('storefront.components.product-card', ['product' => $product])
        @endforeach
    </div>
</div>
