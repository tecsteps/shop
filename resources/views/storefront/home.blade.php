<div>
    @foreach ((array) data_get($settings, 'home.sections', []) as $section)
        @if ($section === 'hero' && data_get($settings, 'home.hero.enabled', true))
            <section class="sf-hero relative isolate min-h-[420px] overflow-hidden bg-slate-950 sm:min-h-[500px] lg:min-h-[600px]" aria-labelledby="hero-heading">
                @if (data_get($settings, 'home.hero.image_url'))
                    <img src="{{ data_get($settings, 'home.hero.image_url') }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high">
                @endif
                <div class="absolute inset-0 -z-10 bg-gradient-to-br from-slate-950/90 via-slate-900/65 to-blue-950/55"></div>
                <div class="sf-container flex min-h-[420px] items-center justify-center py-20 text-center text-white sm:min-h-[500px] lg:min-h-[600px]">
                    <div class="max-w-3xl">
                        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.3em] text-blue-200">{{ $currentStore->name }}</p>
                        <h1 id="hero-heading" class="text-balance text-4xl font-semibold tracking-tight sm:text-6xl lg:text-7xl">
                            {{ data_get($settings, 'home.hero.heading') }}
                        </h1>
                        <p class="mx-auto mt-6 max-w-2xl text-pretty text-base leading-7 text-slate-200 sm:text-xl">
                            {{ data_get($settings, 'home.hero.subheading') }}
                        </p>
                        <a href="{{ url(data_get($settings, 'home.hero.cta_url', '/collections')) }}" wire:navigate class="sf-button sf-button-primary mt-9 inline-flex min-h-12 px-7 text-base">
                            {{ data_get($settings, 'home.hero.cta_label', 'Shop now') }}
                            <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.69L10.72 5.53a.75.75 0 0 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>
            </section>
        @elseif ($section === 'featured_collections' && $collections->isNotEmpty())
            <section class="sf-section" aria-labelledby="featured-collections-heading">
                <div class="sf-container">
                    <div class="mb-9 flex items-end justify-between gap-4">
                        <div>
                            <p class="sf-eyebrow">Curated for you</p>
                            <h2 id="featured-collections-heading" class="sf-section-title">Shop collections</h2>
                        </div>
                        <a href="{{ url('/collections') }}" wire:navigate class="sf-text-link hidden sm:inline-flex">View all <span aria-hidden="true">&rarr;</span></a>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                        @foreach ($collections as $collection)
                            @php($image = optional(optional($collection->products->first())->media->first())->storage_key)
                            <a href="{{ url('/collections/'.$collection->handle) }}" wire:navigate class="group relative isolate aspect-[3/4] overflow-hidden rounded-2xl bg-slate-100 shadow-sm dark:bg-slate-800">
                                @if ($image)
                                    <img src="{{ Storage::disk('public')->url($image) }}" alt="{{ $collection->title }}" loading="lazy" class="absolute inset-0 -z-20 h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105">
                                @else
                                    <div class="absolute inset-0 -z-20 bg-gradient-to-br from-slate-200 to-blue-100 dark:from-slate-800 dark:to-blue-950"></div>
                                @endif
                                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-950/85 via-slate-950/10 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-4 text-white sm:p-6">
                                    <h3 class="text-lg font-semibold sm:text-xl">{{ $collection->title }}</h3>
                                    <span class="mt-1 inline-flex text-sm text-white/80 group-hover:text-white">Shop now <span aria-hidden="true" class="ml-1">&rarr;</span></span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ url('/collections') }}" wire:navigate class="sf-button sf-button-secondary mt-6 w-full sm:hidden">View all collections</a>
                </div>
            </section>
        @elseif ($section === 'featured_products')
            <section class="sf-section bg-slate-50 dark:bg-slate-950/45" aria-labelledby="featured-products-heading">
                <div class="sf-container">
                    <div class="mb-9 flex items-end justify-between gap-4">
                        <div>
                            <p class="sf-eyebrow">New and noteworthy</p>
                            <h2 id="featured-products-heading" class="sf-section-title">Featured products</h2>
                        </div>
                    </div>
                    <livewire:storefront.featured-products :limit="8" lazy />
                </div>
            </section>
        @elseif ($section === 'newsletter')
            <livewire:storefront.newsletter-signup />
        @elseif ($section === 'rich_text' && data_get($settings, 'home.rich_text.content'))
            <section class="sf-section">
                <div class="sf-container max-w-3xl">
                    <x-storefront.rich-text :html="data_get($settings, 'home.rich_text.content')" />
                </div>
            </section>
        @endif
    @endforeach
</div>
