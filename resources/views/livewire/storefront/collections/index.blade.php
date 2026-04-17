<div>
    <h1 class="text-3xl font-bold tracking-tight">Collections</h1>

    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($collections as $collection)
            <a wire:key="collection-{{ $collection->id }}"
               href="{{ route('storefront.collections.show', $collection->handle) }}"
               class="group rounded-lg border border-zinc-200 p-6 transition-colors hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                <h2 class="text-lg font-semibold group-hover:text-zinc-600 dark:group-hover:text-zinc-300">
                    {{ $collection->title }}
                </h2>
                @if($collection->description_html)
                    <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ strip_tags($collection->description_html) }}
                    </p>
                @endif
            </a>
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-zinc-500 dark:text-zinc-400">No collections available yet.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $collections->links() }}
    </div>
</div>
