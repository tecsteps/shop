<div>
    {{-- Hero Section --}}
    <section class="relative bg-zinc-900 dark:bg-zinc-950 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-zinc-800 to-zinc-900 dark:from-zinc-900 dark:to-black"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:py-40 text-center">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white tracking-tight">
                {{ $heroHeading }}
            </h1>
            @if ($heroSubheading)
                <p class="mt-6 text-lg sm:text-xl text-zinc-300 max-w-2xl mx-auto">
                    {{ $heroSubheading }}
                </p>
            @endif
            @if ($heroCtaText)
                <div class="mt-8">
                    <a
                        href="{{ $heroCtaLink }}"
                        class="inline-flex items-center px-8 py-3 bg-white text-zinc-900 font-semibold rounded-lg hover:bg-zinc-100 transition-colors"
                        wire:navigate
                    >
                        {{ $heroCtaText }}
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- Featured Collections --}}
    @if ($featuredCollections->count())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white text-center mb-8">
                Shop by Collection
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach ($featuredCollections as $collection)
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
                            <span class="text-sm text-white/70 underline group-hover:text-white/100 transition-colors">
                                Shop now
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured Products --}}
    @if ($featuredProducts->count())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white text-center mb-8">
                Featured Products
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach ($featuredProducts as $product)
                    <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
