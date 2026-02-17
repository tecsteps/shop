<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.components.breadcrumbs :items="[
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Collections', 'url' => null],
        ]" class="mb-6" />

        <h1 class="mb-8 text-3xl font-bold text-gray-900 dark:text-white">Collections</h1>

        @if($collections->isNotEmpty())
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                @foreach($collections as $collection)
                    <a href="/collections/{{ $collection->handle }}"
                       class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-100 transition-transform hover:scale-[1.02] dark:bg-gray-800">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 p-4">
                            <h2 class="text-base font-semibold text-white sm:text-lg">{{ $collection->title }}</h2>
                            <span class="mt-1 text-sm text-white/70 underline group-hover:text-white/100">
                                Shop now
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="py-16 text-center">
                <p class="text-gray-500 dark:text-gray-400">No collections available yet.</p>
            </div>
        @endif
    </div>
</div>
