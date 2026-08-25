<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-storefront-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => 'Collections'],
        ]" />

        <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
            Collections
        </h1>
        <p class="mt-3 max-w-2xl text-zinc-600 dark:text-zinc-300">
            Browse all of our curated collections.
        </p>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        @if ($this->collections->isEmpty())
            <div class="py-20 text-center">
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">No collections yet</p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Check back soon — we are building something great.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->collections as $collection)
                    @php
                        $url = route('storefront.collection', ['handle' => $collection->handle]);
                    @endphp
                    <a
                        href="{{ $url }}"
                        class="group relative block aspect-[3/4] overflow-hidden rounded-2xl bg-zinc-200 transition duration-300 hover:scale-[1.02] dark:bg-zinc-800"
                        aria-label="{{ $collection->title }}"
                    >
                        <span class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-zinc-950/20 to-transparent" aria-hidden="true"></span>
                        <span class="absolute inset-x-0 bottom-0 p-5">
                            <span class="block text-xl font-semibold text-white">{{ $collection->title }}</span>
                            <span class="mt-1 block text-sm text-white/80 underline underline-offset-2 transition group-hover:text-white">
                                Shop now
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>

            <x-storefront-pagination :paginator="$this->collections" />
        @endif
    </div>
</div>
