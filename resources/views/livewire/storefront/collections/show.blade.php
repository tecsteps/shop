<div>
    <x-slot:title>{{ $collection->title }}</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">{{ $collection->title }}</h1>

        @if ($collection->description_html)
            <div class="prose mt-2 max-w-none text-gray-600 dark:text-gray-400">
                {!! $collection->description_html !!}
            </div>
        @endif

        {{-- Filters and Sort --}}
        <div class="mt-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $products->count() }} {{ Str::plural('product', $products->count()) }}
            </p>
            <div class="flex items-center space-x-4">
                <select wire:model.live="sortBy"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="featured">Sort by: Featured</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="newest">Newest</option>
                </select>
            </div>
        </div>

        {{-- Product Grid --}}
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($products as $product)
                @php
                    $defaultVariant = $product->variants->first();
                    $price = $defaultVariant ? number_format($defaultVariant->price_amount / 100, 2) : '0.00';
                    $currency = $defaultVariant->currency ?? 'EUR';
                @endphp
                <a href="/products/{{ $product->handle }}"
                   class="group block rounded-lg border border-gray-200 p-4 transition hover:shadow-md dark:border-gray-700">
                    <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
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
                <div class="col-span-full rounded-lg border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">No products found in this collection</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
