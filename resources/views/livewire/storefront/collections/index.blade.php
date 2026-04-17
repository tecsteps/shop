<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Collections</h1>
        <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @forelse($this->collections as $collection)
                <a href="/collections/{{ $collection->handle }}" class="group">
                    <div class="aspect-[3/4] overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-700">
                        <div class="flex h-full items-end p-4">
                            <h2 class="text-lg font-semibold text-zinc-900 group-hover:underline dark:text-white">{{ $collection->title }}</h2>
                        </div>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-center text-zinc-500 dark:text-zinc-400">No collections found.</p>
            @endforelse
        </div>
    </div>
</div>
