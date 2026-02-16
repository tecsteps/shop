<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('storefront.home')], ['label' => 'Collections']]" class="mb-6" />

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Collections</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400">Browse our curated collections.</p>

    @if ($collections->isNotEmpty())
        <div class="mt-10 grid grid-cols-2 gap-6 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-800">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4">
                        <p class="text-lg font-semibold text-white">{{ $collection->title }}</p>
                        <p class="mt-1 text-sm text-white/80">{{ $collection->products_count }} {{ Str::plural('product', $collection->products_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="mt-10 flex aspect-[3/4] items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            <p class="text-sm">No collections available yet.</p>
        </div>
    @endif
</div>
