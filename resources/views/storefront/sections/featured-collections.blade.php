@if ($featuredCollections->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="featured-collections-heading">
        <h2 id="featured-collections-heading" class="text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
            {{ __('Shop by collection') }}
        </h2>
        <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 lg:mt-10 lg:grid-cols-4">
            @foreach ($featuredCollections as $featuredCollection)
                @include('storefront.partials.collection-card', ['collection' => $featuredCollection])
            @endforeach
        </div>
    </section>
@endif
