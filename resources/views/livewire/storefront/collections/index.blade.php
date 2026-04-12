<div class="space-y-10">
    <header class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Shop</p>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">All collections</h1>
        <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">Browse every curated edit across the store.</p>
    </header>

    @if ($collections->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">No collections yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a
                    href="{{ route('storefront.collections.show', ['handle' => $collection->handle]) }}"
                    class="group relative block overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="aspect-[4/3] bg-gradient-to-br from-zinc-100 to-zinc-200 transition duration-500 group-hover:scale-105 dark:from-zinc-800 dark:to-zinc-900"></div>
                    <div class="space-y-2 p-5">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ $collection->title }}</h2>
                        @if ($collection->description_html)
                            <div class="line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {!! strip_tags($collection->description_html) !!}
                            </div>
                        @endif
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                            {{ $collection->products_count }} {{ \Illuminate\Support\Str::plural('product', $collection->products_count) }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
