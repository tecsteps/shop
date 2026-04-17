<div>
    <x-slot:title>Home</x-slot:title>

    {{-- Hero Section --}}
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 px-4 py-24 text-center text-white dark:from-blue-800 dark:to-blue-950">
        <div class="mx-auto max-w-3xl">
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ $themeSettings['hero']['heading'] ?? 'Welcome to our store' }}
            </h1>
            <p class="mt-4 text-lg text-blue-100 sm:text-xl">
                {{ $themeSettings['hero']['subheading'] ?? 'Discover our collection' }}
            </p>
            <a href="{{ $themeSettings['hero']['cta_link'] ?? '/collections' }}"
               class="mt-8 inline-block rounded-lg bg-white px-8 py-3 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50">
                {{ $themeSettings['hero']['cta_text'] ?? 'Shop Now' }}
            </a>
        </div>
    </section>

    {{-- Featured Collections --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Featured Collections</h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Browse our curated collections</p>
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($collections as $collection)
                <a href="/collections/{{ $collection->handle }}"
                   class="group block rounded-lg border border-gray-200 bg-gray-50 p-8 text-center transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold group-hover:text-blue-600">{{ $collection->title }}</h3>
                    @if ($collection->description_html)
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit(strip_tags($collection->description_html), 80) }}</p>
                    @endif
                </a>
            @empty
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">Collections coming soon</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Featured Products --}}
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold">Featured Products</h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Check out our latest arrivals</p>
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($products as $product)
                    @php
                        $defaultVariant = $product->variants->first();
                        $price = $defaultVariant ? number_format($defaultVariant->price_amount / 100, 2) : '0.00';
                        $currency = $defaultVariant->currency ?? 'EUR';
                    @endphp
                    <a href="/products/{{ $product->handle }}"
                       class="group block rounded-lg border border-gray-200 bg-white p-4 transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                            <div class="flex h-full items-center justify-center text-gray-400">
                                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="mt-3 text-sm font-medium group-hover:text-blue-600">{{ $product->title }}</h3>
                        <p class="mt-1 text-sm font-semibold">{{ $price }} {{ $currency }}</p>
                    </a>
                @empty
                    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-gray-500 dark:text-gray-400">Products coming soon</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Newsletter --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-blue-600 px-6 py-16 text-center dark:bg-blue-800 sm:px-12">
            <h2 class="text-2xl font-bold text-white">Stay in the loop</h2>
            <p class="mt-2 text-blue-100">Subscribe to our newsletter for exclusive deals and updates.</p>
            <form class="mx-auto mt-6 flex max-w-md gap-2">
                <input type="email" placeholder="Enter your email"
                       class="flex-1 rounded-lg border-0 px-4 py-2 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-white">
                <button type="button"
                        class="rounded-lg bg-white px-6 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">
                    Subscribe
                </button>
            </form>
        </div>
    </section>
</div>
