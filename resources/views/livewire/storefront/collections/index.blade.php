<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => '/'], ['label' => 'Collections']]" />

        <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">Collections</h1>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
            @forelse($this->collections as $collection)
                <a href="/collections/{{ $collection->handle }}"
                   class="group relative overflow-hidden rounded-lg transition hover:scale-[1.02]"
                   wire:key="collection-{{ $collection->id }}">
                    <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-800">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 p-4">
                        <h3 class="text-lg font-semibold text-white">{{ $collection->title }}</h3>
                        <span class="mt-1 inline-block text-sm text-white/70 underline group-hover:text-white">Shop now</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400">No collections available.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
