@if ($featuredProducts->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="featured-products-heading">
        <h2 id="featured-products-heading" class="text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
            {{ __('Featured products') }}
        </h2>
        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-6 md:grid-cols-3 lg:mt-10 lg:grid-cols-4">
            @foreach ($featuredProducts as $featuredProduct)
                <x-storefront.product-card :product="$featuredProduct" />
            @endforeach
        </div>
    </section>
@endif
