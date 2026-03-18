<div>
    {{-- Hero Banner --}}
    <section class="relative flex min-h-[300px] items-center justify-center bg-gradient-to-br from-zinc-900 to-zinc-700 px-4 py-16 text-white sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative z-10 mx-auto max-w-3xl text-center">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl lg:text-6xl">
                {{ $themeSettings['hero_heading'] ?? 'Welcome to our store' }}
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-base text-white/80 sm:text-lg md:mt-6 md:text-xl">
                {{ $themeSettings['hero_subheading'] ?? 'Discover our latest collection' }}
            </p>
            <a href="{{ $themeSettings['hero_cta_link'] ?? '/collections' }}"
               class="mt-6 inline-block rounded-lg bg-white px-6 py-3 text-sm font-semibold text-zinc-900 shadow-lg transition hover:bg-zinc-100 md:mt-8 md:px-8 md:py-4 md:text-base">
                {{ $themeSettings['hero_cta_text'] ?? 'Shop now' }}
            </a>
        </div>
    </section>

    {{-- Featured Collections --}}
    @if($this->featuredCollections->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <h2 class="mb-8 text-center text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                Shop by Collection
            </h2>
            <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                @foreach($this->featuredCollections as $collection)
                    <a href="/collections/{{ $collection->handle }}"
                       class="group relative overflow-hidden rounded-lg"
                       wire:key="collection-{{ $collection->id }}">
                        <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-800">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <h3 class="text-lg font-semibold text-white">{{ $collection->title }}</h3>
                            <span class="mt-1 inline-block text-sm text-white/70 underline group-hover:text-white">Shop now</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured Products --}}
    @if($this->featuredProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <h2 class="mb-8 text-center text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                Featured Products
            </h2>
            <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                @foreach($this->featuredProducts as $product)
                    <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Newsletter --}}
    <section class="bg-zinc-100 px-4 py-12 dark:bg-zinc-900 sm:py-16">
        <div class="mx-auto max-w-xl text-center">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white sm:text-2xl">Stay in the loop</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and updates.</p>
            <form class="mt-6 flex gap-2" onsubmit="event.preventDefault()">
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input type="email" id="newsletter-email" placeholder="Enter your email" required
                       class="flex-1 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500">
                <button type="submit"
                        class="shrink-0 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Subscribe
                </button>
            </form>
        </div>
    </section>
</div>
