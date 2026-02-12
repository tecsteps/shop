<div>
    {{-- Hero Banner --}}
    <section class="relative bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-800 dark:to-gray-950 min-h-[300px] sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px] flex items-center">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white tracking-tight">
                {{ $hero['heading'] ?? 'Welcome' }}
            </h1>
            <p class="mt-4 sm:mt-6 text-base sm:text-lg md:text-xl text-gray-200 max-w-2xl mx-auto">
                {{ $hero['subheading'] ?? '' }}
            </p>
            <div class="mt-6 sm:mt-8">
                <a href="{{ $hero['cta_link'] ?? '/collections' }}"
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors text-sm sm:text-base">
                    {{ $hero['cta_text'] ?? 'Shop Now' }}
                </a>
            </div>
        </div>
    </section>

    {{-- Featured Collections --}}
    @if($featuredCollections->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white text-center mb-8">
                Shop by Collection
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($featuredCollections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection->handle) }}"
                       class="group relative aspect-[3/4] bg-gray-200 dark:bg-gray-800 rounded-lg overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <h3 class="text-white font-semibold text-base sm:text-lg">{{ $collection->title }}</h3>
                            <p class="text-white/70 text-sm mt-1 group-hover:text-white/100 underline transition-colors">
                                Shop now
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured Products --}}
    @if($featuredProducts->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white text-center mb-8">
                Featured Products
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($featuredProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Newsletter --}}
    <section class="bg-gray-50 dark:bg-gray-900 py-12 lg:py-16">
        <div class="max-w-xl mx-auto px-4 sm:px-6 text-center">
            <h2 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-white">Stay in the loop</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Subscribe for exclusive offers and updates.</p>
            <form class="mt-6 flex gap-2" onsubmit="event.preventDefault()">
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input type="email" id="newsletter-email" placeholder="Enter your email" required
                       class="flex-1 min-w-0 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors whitespace-nowrap">
                    Subscribe
                </button>
            </form>
        </div>
    </section>
</div>
