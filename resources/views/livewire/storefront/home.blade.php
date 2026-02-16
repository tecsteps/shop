<div>
    {{-- Hero Section --}}
    <section class="relative flex min-h-[300px] items-center justify-center bg-gradient-to-br from-gray-900 to-gray-700 px-4 py-20 text-white sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]">
        <div class="mx-auto max-w-3xl text-center">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-6xl">Welcome to {{ $storeName }}</h1>
            <p class="mx-auto mt-6 max-w-xl text-lg text-gray-300">Discover our curated collection of premium products crafted for quality and style.</p>
            <a href="{{ url('/collections') }}" class="mt-8 inline-flex items-center rounded-lg bg-white px-8 py-3 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-100">
                Shop Collections
            </a>
        </div>
    </section>

    {{-- Featured Products --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Featured Products</h2>
            <div class="mt-10 grid grid-cols-2 gap-6 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured Collections --}}
    @if ($collections->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Shop by Collection</h2>
            <div class="mt-10 grid grid-cols-2 gap-6 lg:grid-cols-4">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-800">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-4 left-4">
                            <p class="text-lg font-semibold text-white">{{ $collection->title }}</p>
                            <p class="mt-1 text-sm text-white/80 underline">Shop now</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Newsletter --}}
    <section class="bg-gray-100 px-4 py-16 dark:bg-gray-900">
        <div class="mx-auto max-w-xl text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Stay in the loop</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Subscribe for exclusive offers and updates.</p>
            <div class="mt-6 flex gap-3">
                <input type="email" placeholder="Enter your email" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <button class="shrink-0 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Subscribe</button>
            </div>
        </div>
    </section>
</div>
