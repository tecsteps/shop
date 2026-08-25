@if ($featuredCollections->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="featured-collections-heading">
        <h2 id="featured-collections-heading" class="text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
            Shop by collection
        </h2>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
            @foreach ($featuredCollections as $collection)
                @php
                    $url = route('storefront.collection', ['handle' => $collection->handle]);
                @endphp
                <a
                    href="{{ $url }}"
                    class="group relative block aspect-[3/4] overflow-hidden rounded-xl bg-zinc-200 transition duration-300 hover:scale-[1.02] dark:bg-zinc-800"
                    aria-label="{{ $collection->title }}"
                >
                    <span class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-zinc-950/20 to-transparent" aria-hidden="true"></span>
                    <span class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                        <span class="block text-base font-semibold text-white sm:text-lg">{{ $collection->title }}</span>
                        <span class="mt-1 block text-sm text-white/80 underline underline-offset-2 transition group-hover:text-white">
                            Shop now
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
