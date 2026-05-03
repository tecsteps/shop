<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-normal">Collections</h1>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($collections as $collection)
            <a wire:key="collection-{{ $collection->id }}" href="/collections/{{ $collection->handle }}" class="rounded-lg border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                <h2 class="font-semibold">{{ $collection->title }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ strip_tags($collection->description_html) }}</p>
            </a>
        @endforeach
    </div>
</div>
