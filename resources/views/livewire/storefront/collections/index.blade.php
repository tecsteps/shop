<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections', 'url' => ''],
    ]" />

    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-8">Collections</h1>

    @if($collections->isEmpty())
        <div class="text-center py-16">
            <p class="text-gray-500 dark:text-gray-400">No collections available.</p>
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach($collections as $collection)
                <a href="{{ route('storefront.collections.show', $collection->handle) }}"
                   class="group relative aspect-[3/4] bg-gray-200 dark:bg-gray-800 rounded-lg overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-6">
                        <h2 class="text-white font-semibold text-lg sm:text-xl">{{ $collection->title }}</h2>
                        <p class="text-white/70 text-sm mt-1">{{ $collection->products_count }} {{ Str::plural('product', $collection->products_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
