<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[['label' => 'Collections']]" />

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Collections</h1>

        @if($collections->isEmpty())
            <div class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No collections yet</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Check back soon for our curated collections.</p>
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                @foreach($collections as $collection)
                    <a href="/collections/{{ $collection->handle }}"
                       wire:key="collection-{{ $collection->id }}"
                       class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-200 transition-transform duration-300 hover:scale-[1.02] dark:bg-gray-800">
                        @if($collection->image_url)
                            <img src="{{ $collection->image_url }}"
                                 alt="{{ $collection->title }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 p-4">
                            <p class="text-sm font-semibold text-white md:text-base">{{ $collection->title }}</p>
                            <p class="mt-1 text-xs text-white/70 underline group-hover:text-white/100">Shop now</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
