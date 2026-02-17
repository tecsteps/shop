<div>
    @php
        $hero = $themeSettings->get('hero', []);
    @endphp

    {{-- Hero banner --}}
    <section class="relative flex min-h-[300px] items-center justify-center bg-gradient-to-br from-gray-900 to-gray-700 px-4 py-20 text-white sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]">
        <div class="relative z-10 mx-auto max-w-3xl text-center">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl lg:text-6xl">
                {{ $hero['heading'] ?? 'Welcome to Our Store' }}
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-base text-gray-300 sm:text-lg">
                {{ $hero['subheading'] ?? 'Discover amazing products at great prices.' }}
            </p>
            @if(!empty($hero['cta_text']))
                <a href="{{ $hero['cta_link'] ?? '/collections' }}"
                   class="mt-8 inline-block rounded-md bg-blue-600 px-8 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ $hero['cta_text'] }}
                </a>
            @endif
        </div>
    </section>

    {{-- Featured collections --}}
    @if($collections->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
                Featured Collections
            </h2>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                @foreach($collections as $collection)
                    <a href="/collections/{{ $collection->handle }}"
                       class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 p-4">
                            <h3 class="text-base font-semibold text-white sm:text-lg">{{ $collection->title }}</h3>
                            <span class="mt-1 text-sm text-white/70 underline group-hover:text-white/100">
                                Shop now
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured products --}}
    @if($featuredProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
                Featured Products
            </h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                @foreach($featuredProducts as $product)
                    <x-storefront.components.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Newsletter --}}
    <section class="bg-gray-100 px-4 py-16 dark:bg-gray-900">
        <div class="mx-auto max-w-xl text-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">Stay in the loop</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Subscribe for exclusive offers and updates.</p>
            <form class="mt-6 flex gap-3" onsubmit="event.preventDefault()">
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input type="email"
                       id="newsletter-email"
                       placeholder="Enter your email"
                       required
                       class="flex-1 rounded-md border border-gray-300 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400">
                <button type="submit"
                        class="rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                    Subscribe
                </button>
            </form>
        </div>
    </section>
</div>
