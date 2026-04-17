<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => 'Collections'],
        ]" class="mb-6" />

        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-8">Collections</h1>

        @if ($collections->count())
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach ($collections as $collection)
                    <a
                        href="{{ route('storefront.collections.show', $collection->handle) }}"
                        class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800"
                        wire:navigate
                        wire:key="collection-{{ $collection->id }}"
                    >
                        @if ($collection->image_url)
                            <img
                                src="{{ $collection->image_url }}"
                                alt="{{ $collection->title }}"
                                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <h3 class="text-lg font-semibold text-white">{{ $collection->title }}</h3>
                            <p class="text-sm text-white/70">{{ $collection->products_count }} {{ Str::plural('product', $collection->products_count) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-16">
                <flux:icon name="rectangle-stack" class="size-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                <p class="text-zinc-500 dark:text-zinc-400">No collections available.</p>
            </div>
        @endif
    </div>
</div>
