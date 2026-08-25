@if ($featuredProducts->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="featured-products-heading">
        <h2 id="featured-products-heading" class="text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
            Featured products
        </h2>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($featuredProducts as $product)
                <x-storefront-product-card :product="$product" />
            @endforeach
        </div>
    </section>
@endif
