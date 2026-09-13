<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Collections']]" />
    <h1 class="mt-4 text-3xl font-bold">Collections</h1>

    @if ($collections->isEmpty())
        <p class="mt-8 text-center text-zinc-500 dark:text-zinc-400">No collections yet.</p>
    @else
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($collections as $collection)
                <a href="{{ route('storefront.collections.show', ['handle' => $collection->handle]) }}" wire:key="collection-{{ $collection->getKey() }}" class="group relative block overflow-hidden rounded-lg transition-transform hover:scale-[1.02]">
                    <div class="flex aspect-[3/4] items-end bg-gradient-to-t from-zinc-900 to-zinc-400 p-4">
                        <div>
                            <p class="font-semibold text-white">{{ $collection->title }}</p>
                            <p class="text-sm text-white/70">{{ $collection->products_count }} products</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        <x-storefront.pagination :paginator="$collections" />
    @endif
</div>
