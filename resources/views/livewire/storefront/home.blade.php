<div>
    @if (($settings['hero']['show'] ?? true))
        <section class="relative flex min-h-[300px] items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-800 to-zinc-950 px-4 py-16 text-center text-white sm:min-h-[400px] lg:min-h-[600px]">
            <div>
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">{{ $settings['hero']['heading'] ?? $store->name }}</h1>
                @if (($settings['hero']['subheading'] ?? '') !== '')
                    <p class="mx-auto mt-4 max-w-xl text-lg">{{ $settings['hero']['subheading'] }}</p>
                @endif
                <a href="{{ $settings['hero']['cta_url'] ?? '/collections' }}" class="mt-6 inline-block rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">{{ $settings['hero']['cta_text'] ?? 'Shop now' }}</a>
            </div>
        </section>
    @endif

    @if ($collections->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12" aria-label="Featured collections">
            <h2 class="mb-6 text-center text-2xl font-bold">Featured Collections</h2>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collections.show', ['handle' => $collection->handle]) }}" wire:key="home-collection-{{ $collection->getKey() }}" class="group relative block overflow-hidden rounded-lg transition-transform hover:scale-[1.02]">
                        <div class="flex aspect-[3/4] items-end bg-gradient-to-t from-zinc-900 to-zinc-400 p-4">
                            <div>
                                <p class="font-semibold text-white">{{ $collection->title }}</p>
                                <p class="text-sm text-white/70 underline group-hover:text-white">Shop now</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 py-12" aria-label="Featured products">
        <livewire:storefront.featured-products :count="(int) ($settings['home']['featured_products_count'] ?? 8)" />
    </section>

    @if (($settings['home']['show_newsletter'] ?? true))
        <livewire:storefront.newsletter-signup />
    @endif

    @if (($settings['home']['rich_text_html'] ?? null))
        <section class="mx-auto max-w-3xl px-4 py-12">
            <div class="prose dark:prose-invert">{!! $settings['home']['rich_text_html'] !!}</div>
        </section>
    @endif
</div>
