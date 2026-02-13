<div>
    {{-- Hero --}}
    <section class="py-12 text-center">
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
            {{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
            Discover our latest collections and products.
        </p>
        <div class="mt-8">
            <flux:button variant="primary" href="{{ route('storefront.collections.index') }}">
                Browse Collections
            </flux:button>
        </div>
    </section>

    {{-- Featured Collections --}}
    @if($collections->isNotEmpty())
        <section class="py-12">
            <h2 class="text-2xl font-bold tracking-tight">Collections</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($collections as $collection)
                    <a wire:key="collection-{{ $collection->id }}"
                       href="{{ route('storefront.collections.show', $collection->handle) }}"
                       class="group rounded-lg border border-zinc-200 p-6 transition-colors hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                        <h3 class="text-lg font-semibold group-hover:text-zinc-600 dark:group-hover:text-zinc-300">
                            {{ $collection->title }}
                        </h3>
                        @if($collection->description_html)
                            <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ strip_tags($collection->description_html) }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
