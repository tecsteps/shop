<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections'],
    ]" />

    <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">Collections</h1>

    @if($collections->isNotEmpty())
        <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            @foreach($collections as $collection)
                <a href="{{ route('storefront.collections.show', $collection->handle) }}"
                   class="group relative block overflow-hidden rounded-lg aspect-[3/4]">
                    <div class="absolute inset-0 bg-zinc-200 dark:bg-zinc-700 transition-transform duration-300 group-hover:scale-105"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-4">
                        <h2 class="text-lg font-semibold text-white">{{ $collection->title }}</h2>
                        <span class="mt-1 text-sm text-white/70 underline group-hover:text-white/100 transition-colors">
                            Shop now
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="mt-16 text-center">
            <p class="text-zinc-500 dark:text-zinc-400">No collections available at the moment.</p>
        </div>
    @endif
</div>
