<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Collections</h1>
        <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
            @foreach($collections as $collection)
                <a href="/collections/{{ $collection->handle }}" wire:key="col-{{ $collection->id }}" class="group block overflow-hidden rounded-lg bg-gray-100 p-6 text-center transition hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $collection->title }}</h3>
                    @if($collection->description_html)
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit(strip_tags($collection->description_html), 80) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
